<?php declare( strict_types=1 );

use CirrusSearch\CirrusDebugOptions;
use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Reporter\ErrorReporter;
use MediaWiki\Rest\Reporter\MWErrorReporter;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\Api\CombinedEntitySearchHelper;
use Wikibase\Repo\Api\EntitySearchHelper;
use Wikibase\Repo\Api\PropertyDataTypeSearchHelper;
use Wikibase\Repo\ControllerRegistry;
use Wikibase\Repo\Domains\Search\Application\UseCases\ItemPrefixSearch\ItemPrefixSearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\ItemPrefixSearch\ItemPrefixSearchValidator;
use Wikibase\Repo\Domains\Search\Application\UseCases\PropertyPrefixSearch\PropertyPrefixSearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\PropertyPrefixSearch\PropertyPrefixSearchValidator;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch\SimpleItemSearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch\SimpleItemSearchValidator;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimplePropertySearch\SimplePropertySearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimplePropertySearch\SimplePropertySearchValidator;
use Wikibase\Repo\Domains\Search\Application\Validation\SearchLanguageValidator;
use Wikibase\Repo\Domains\Search\Infrastructure\Controllers\DispatchingWbSearchEntitiesController;
use Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\EntitySearchHelperFactory;
use Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\EntitySearchHelperPrefixSearchEngine;
use Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\InLabelSearchEngine;
use Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\TermsTablesEntitySearchHelperFactory;
use Wikibase\Repo\Domains\Search\Infrastructure\LanguageCodeValidator;
use Wikibase\Repo\Domains\Search\RouteHandlers\SearchExceptionMiddleware;
use Wikibase\Repo\Domains\Search\WbSearch;
use Wikibase\Repo\RestApi\Middleware\MiddlewareHandler;
use Wikibase\Repo\RestApi\Middleware\UnexpectedErrorHandlerMiddleware;
use Wikibase\Repo\RestApi\Middleware\UserAgentCheckMiddleware;
use Wikibase\Repo\Validators\CompositeValidator;
use Wikibase\Repo\Validators\MembershipValidator;
use Wikibase\Repo\Validators\NotMulValidator;
use Wikibase\Repo\Validators\TypeValidator;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Search\Elastic\InLabelSearch;
use Wikibase\Search\Elastic\WikibaseCirrusSearch;

/** @phpcs-require-sorted-array */
return [
	'WbSearch.DispatchingWbSearchEntitiesController' => function( MediaWikiServices $services ): DispatchingWbSearchEntitiesController {
		return new DispatchingWbSearchEntitiesController(
			WikibaseRepo::getControllerRegistry( $services )
				->get( ControllerRegistry::WB_SEARCH_ENTITIES_CONTROLLER )
		);
	},

	'WbSearch.EntitySearchHelperFactory' => function ( MediaWikiServices $services ): EntitySearchHelperFactory {
		if ( $services->getExtensionRegistry()->isLoaded( 'WikibaseCirrusSearch' )
			&& $services->getMainConfig()->get( 'WBCSUseCirrus' ) ) {
			// @phan-suppress-next-line PhanUndeclaredClassMethod WikibaseCirrusSearch is ok here
			return WikibaseCirrusSearch::getEntitySearchHelperFactory( $services );
		}
		return new TermsTablesEntitySearchHelperFactory(
			WikibaseRepo::getEntityLookup( $services ),
			WikibaseRepo::getEntityIdParser( $services ),
			WikibaseRepo::getEntitySourceDefinitions( $services ),
			WikibaseRepo::getFallbackLabelDescriptionLookupFactory( $services ),
			WikibaseRepo::getEnabledEntityTypes( $services ),
			WikibaseRepo::getMatchingTermsLookupFactory( $services ),
			WikibaseRepo::getLanguageFallbackChainFactory( $services ),
			WikibaseRepo::getPrefetchingTermLookup( $services ),
		);
	},

	'WbSearch.ErrorReporter' => function ( MediaWikiServices $services ): ErrorReporter {
		return new MWErrorReporter();
	},

	'WbSearch.InLabelSearchEngine' => function( MediaWikiServices $services ): InLabelSearchEngine {
		// @phan-suppress-next-line PhanUndeclaredClassMethod
		return new InLabelSearchEngine( new InLabelSearch(
			WikibaseRepo::getLanguageFallbackChainFactory( $services ),
			WikibaseRepo::getEntityIdParser( $services ),
			WikibaseRepo::getContentModelMappings( $services ),
			CirrusDebugOptions::fromRequest( RequestContext::getMain()->getRequest() ),
			$services->getConfigFactory()
				->makeConfig( 'WikibaseCirrusSearch' )
				->get( 'UseStemming' )
		) );
	},

	'WbSearch.ItemPrefixSearch' => function( MediaWikiServices $services ): ItemPrefixSearch {
		$searchProfiles = WikibaseRepo::getSettings( $services )->getSetting( 'searchProfiles' );
		return new ItemPrefixSearch(
			new ItemPrefixSearchValidator( WbSearch::getLanguageCodeValidator( $services ) ),
			new EntitySearchHelperPrefixSearchEngine(
				// @phan-suppress-next-line PhanUndeclaredClassMethod WikibaseCirrusSearch is ok here
				WikibaseCirrusSearch::getEntitySearchHelperFactory( $services ),
				$services->getLanguageFactory(),
				RequestContext::getMain()->getRequest(),
				$searchProfiles
			),
		);
	},

	'WbSearch.ItemSearchHelper' => function( MediaWikiServices $services ): EntitySearchHelper {
		$context = RequestContext::getMain();
		return WbSearch::getEntitySearchHelperFactory( $services )
			->newEntitySearchHelper( Item::ENTITY_TYPE, $context->getLanguage(), $context->getRequest() );
	},

	'WbSearch.LanguageCodeValidator' => function ( MediaWikiServices $services ): SearchLanguageValidator {
		return new LanguageCodeValidator(
			new CompositeValidator( [
				new TypeValidator( 'string' ),
				new MembershipValidator( WikibaseRepo::getTermsLanguages()->getLanguages(), 'not-a-language' ),
				new NotMulValidator( $services->getLanguageNameUtils() ),
			] )
		);
	},

	'WbSearch.MiddlewareHandler' => function ( MediaWikiServices $services ): MiddlewareHandler {
		return new MiddlewareHandler( [
			WbSearch::getUnexpectedErrorHandlerMiddleware(),
			new SearchExceptionMiddleware(),
			new UserAgentCheckMiddleware(),
		] );
	},

	'WbSearch.PropertyPrefixSearch' => function( MediaWikiServices $services ): PropertyPrefixSearch {
		$searchProfiles = WikibaseRepo::getSettings( $services )->getSetting( 'searchProfiles' );
		return new PropertyPrefixSearch(
			new PropertyPrefixSearchValidator( WbSearch::getLanguageCodeValidator( $services ) ),
			new EntitySearchHelperPrefixSearchEngine(
				// @phan-suppress-next-line PhanUndeclaredClassMethod WikibaseCirrusSearch is ok here
				WikibaseCirrusSearch::getEntitySearchHelperFactory( $services ),
				$services->getLanguageFactory(),
				RequestContext::getMain()->getRequest(),
				$searchProfiles
			)
		);
	},

	'WbSearch.PropertySearchHelper' => function( MediaWikiServices $services ): EntitySearchHelper {
		$context = RequestContext::getMain();
		$federatedPropertiesEnabled = WikibaseRepo::getSettings( $services )->getSetting( 'federatedPropertiesEnabled' );

		$localPropertySearch = new PropertyDataTypeSearchHelper(
			WbSearch::getEntitySearchHelperFactory( $services )
				->newEntitySearchHelper( Property::ENTITY_TYPE, $context->getLanguage(), $context->getRequest() ),
			WikibaseRepo::getPropertyDataTypeLookup( $services )
		);

		if ( $federatedPropertiesEnabled ) {
			return new CombinedEntitySearchHelper( [
				$localPropertySearch,
				WikibaseRepo::getFederatedPropertiesServiceFactory( $services )->newApiEntitySearchHelper(),
			] );
		}

		return $localPropertySearch;
	},

	'WbSearch.SimpleItemSearch' => function( MediaWikiServices $services ): SimpleItemSearch {
		return new SimpleItemSearch(
			new SimpleItemSearchValidator( WbSearch::getLanguageCodeValidator( $services ) ),
			WbSearch::getInLabelSearchEngine( $services )
		);
	},

	'WbSearch.SimplePropertySearch' => function( MediaWikiServices $services ): SimplePropertySearch {
		$validator = new SimplePropertySearchValidator( WbSearch::getLanguageCodeValidator( $services ) );

		return new SimplePropertySearch( $validator, WbSearch::getInLabelSearchEngine( $services )
		);
	},

	'WbSearch.UnexpectedErrorHandlerMiddleware' => function( MediaWikiServices $services ): UnexpectedErrorHandlerMiddleware {
		return new UnexpectedErrorHandlerMiddleware( WbSearch::getErrorReporter( $services ) );
	},
];

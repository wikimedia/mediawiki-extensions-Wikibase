<?php declare( strict_types=1 );

use CirrusSearch\CirrusDebugOptions;
use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Reporter\ErrorReporter;
use MediaWiki\Rest\Reporter\MWErrorReporter;
use Wikibase\Repo\Domains\Search\Application\UseCases\ItemPrefixSearch\ItemPrefixSearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\ItemPrefixSearch\ItemPrefixSearchValidator;
use Wikibase\Repo\Domains\Search\Application\UseCases\PropertyPrefixSearch\PropertyPrefixSearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\PropertyPrefixSearch\PropertyPrefixSearchValidator;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch\SimpleItemSearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch\SimpleItemSearchValidator;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimplePropertySearch\SimplePropertySearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimplePropertySearch\SimplePropertySearchValidator;
use Wikibase\Repo\Domains\Search\Application\Validation\SearchLanguageValidator;
use Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\EntitySearchHelperPrefixSearchEngine;
use Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\InLabelSearchEngine;
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
use Wikibase\Search\Elastic\EntitySearchHelperFactory;
use Wikibase\Search\Elastic\InLabelSearch;

/** @phpcs-require-sorted-array */
return [
	'WbSearch.ErrorReporter' => function( MediaWikiServices $services ): ErrorReporter {
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
		return new ItemPrefixSearch(
			new ItemPrefixSearchValidator( WbSearch::getLanguageCodeValidator( $services ) ),
			new EntitySearchHelperPrefixSearchEngine(
			// @phan-suppress-next-line PhanUndeclaredClassMethod WikibaseCirrusSearch is ok here
				EntitySearchHelperFactory::newFromGlobalState(),
				$services->getLanguageFactory(),
				RequestContext::getMain()->getRequest()
			)
		);
	},

	'WbSearch.LanguageCodeValidator' => function ( MediaWikiServices $services ): SearchLanguageValidator {
		$validators = [];
		$validators[] = new TypeValidator( 'string' );
		$validators[] = new MembershipValidator( WikibaseRepo::getTermsLanguages()->getLanguages(), 'not-a-language' );
		$validators[] = new NotMulValidator( MediaWikiServices::getInstance()->getLanguageNameUtils() );

		return new LanguageCodeValidator(
			new CompositeValidator( $validators )
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
		return new PropertyPrefixSearch(
			new PropertyPrefixSearchValidator( WbSearch::getLanguageCodeValidator( $services ) ),
			new EntitySearchHelperPrefixSearchEngine(
			// @phan-suppress-next-line PhanUndeclaredClassMethod WikibaseCirrusSearch is ok here
				EntitySearchHelperFactory::newFromGlobalState(),
				$services->getLanguageFactory(),
				RequestContext::getMain()->getRequest()
			)
		);
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

<?php declare( strict_types=1 );

use CirrusSearch\CirrusDebugOptions;
use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Reporter\ErrorReporter;
use MediaWiki\Rest\Reporter\MWErrorReporter;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch\SimpleItemSearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch\SimpleItemSearchValidator;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimplePropertySearch\SimplePropertySearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimplePropertySearch\SimplePropertySearchValidator;
use Wikibase\Repo\Domains\Search\Application\Validation\SearchLanguageValidator;
use Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\InLabelSearchEngine;
use Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\SqlTermStoreSearchEngine;
use Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\TermRetriever;
use Wikibase\Repo\Domains\Search\Infrastructure\LanguageCodeValidator;
use Wikibase\Repo\Domains\Search\WbSearch;
use Wikibase\Repo\RestApi\Middleware\UnexpectedErrorHandlerMiddleware;
use Wikibase\Repo\Validators\CompositeValidator;
use Wikibase\Repo\Validators\MembershipValidator;
use Wikibase\Repo\Validators\NotMulValidator;
use Wikibase\Repo\Validators\TypeValidator;
use Wikibase\Repo\WikibaseRepo;
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
			CirrusDebugOptions::fromRequest( RequestContext::getMain()->getRequest() )
		) );
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

	'WbSearch.SimpleItemSearch' => function( MediaWikiServices $services ): SimpleItemSearch {
		global $wgSearchType;

		$isWikibaseCirrusSearchEnabled = $services->getExtensionRegistry()->isLoaded( 'WikibaseCirrusSearch' );
		$isCirrusSearchEnabled = $wgSearchType === 'CirrusSearch';

		$searchEngine = $isCirrusSearchEnabled && $isWikibaseCirrusSearchEnabled
			? WbSearch::getInLabelSearchEngine( $services )
			: new SqlTermStoreSearchEngine(
				WikibaseRepo::getMatchingTermsLookupFactory( $services )
					->getLookupForSource( WikibaseRepo::getLocalEntitySource( $services ) ),
				new TermRetriever( WikibaseRepo::getFallbackLabelDescriptionLookupFactory( $services ), $services->getLanguageFactory() ),
				WikibaseRepo::getLanguageFallbackChainFactory( $services )
			);

		return new SimpleItemSearch(
			new SimpleItemSearchValidator( WbSearch::getLanguageCodeValidator( $services ) ),
			$searchEngine
		);
	},

	'WbSearch.SimplePropertySearch' => function( MediaWikiServices $services ): SimplePropertySearch {
		global $wgSearchType;

		$isWikibaseCirrusSearchEnabled = $services->getExtensionRegistry()->isLoaded( 'WikibaseCirrusSearch' );
		$isCirrusSearchEnabled = $wgSearchType === 'CirrusSearch';

		$searchEngine = $isCirrusSearchEnabled && $isWikibaseCirrusSearchEnabled
			? WbSearch::getInLabelSearchEngine( $services )
			: new SqlTermStoreSearchEngine(
				WikibaseRepo::getMatchingTermsLookupFactory( $services )
					->getLookupForSource( WikibaseRepo::getLocalEntitySource( $services ) ),
				new TermRetriever( WikibaseRepo::getFallbackLabelDescriptionLookupFactory( $services ), $services->getLanguageFactory() ),
				WikibaseRepo::getLanguageFallbackChainFactory( $services )
			);

		$validator = new SimplePropertySearchValidator( WbSearch::getLanguageCodeValidator( $services ) );

		return new SimplePropertySearch( $validator, $searchEngine );
	},

	'WbSearch.UnexpectedErrorHandlerMiddleware' => function( MediaWikiServices $services ): UnexpectedErrorHandlerMiddleware {
		return new UnexpectedErrorHandlerMiddleware( WbSearch::getErrorReporter( $services ) );
	},
];

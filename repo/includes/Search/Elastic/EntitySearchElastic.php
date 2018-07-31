<?php

namespace Wikibase\Repo\Search\Elastic;

use CirrusSearch\CirrusDebugOptions;
use CirrusSearch\Search\SearchContext;
use Elastica\Query\AbstractQuery;
use Elastica\Query\BoolQuery;
use Elastica\Query\DisMax;
use Elastica\Query\Match;
use Elastica\Query\Term;
use Language;
use WebRequest;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Repo\Api\EntitySearchHelper;

/**
 * Entity search implementation using ElasticSearch.
 * Requires CirrusSearch extension and $wgEntitySearchUseCirrus to be on.
 *
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
 */
class EntitySearchElastic implements EntitySearchHelper {
	/**
	 * Default rescore profile
	 */
	const DEFAULT_RESCORE_PROFILE = 'wikibase_prefix';

	/**
	 * Name of the context for profile name resolution
	 */
	const CONTEXT_WIKIBASE_PREFIX = 'wikibase_prefix_search';

	/**
	 * Name of the context for profile name resolution
	 */
	const CONTEXT_WIKIBASE_FULLTEXT = 'wikibase_fulltext_search';

	/**
	 * Name of the profile type used to build the elastic query
	 */
	const WIKIBASE_PREFIX_QUERY_BUILDER = 'wikibase_prefix_querybuilder';

	/**
	 * Default query builder profile
	 */
	const DEFAULT_QUERY_BUILDER_PROFILE = 'default';

	/**
	 * Replacement syntax for statement boosting
	 * @see \CirrusSearch\Profile\SearchProfileRepositoryTransformer
	 * and repo/config/ElasticSearchRescoreFunctions.php
	 */
	const STMT_BOOST_PROFILE_REPL = 'functions.*[type=term_boost].params[statement_keywords=_statementBoost_].statement_keywords';

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageChainFactory;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var string[]
	 */
	private $contentModelMap;

	/**
	 * Web request context.
	 * Used for implementing debug features such as cirrusDumpQuery.
	 * @var \WebRequest
	 */
	private $request;

	/**
	 * List of fallback codes for search language
	 * @var string[]
	 */
	private $searchLanguageCodes = [];

	/**
	 * Wikibase configuration settings for entity search
	 * @var array
	 */
	private $settings;

	/**
	 * @var Language User language for display.
	 */
	private $userLang;

	/**
	 * @var CirrusDebugOptions
	 */
	private $debugOptions;

	/**
	 * @param LanguageFallbackChainFactory $languageChainFactory
	 * @param EntityIdParser $idParser
	 * @param Language $userLang
	 * @param array $contentModelMap Maps entity type => content model name
	 * @param array $settings Search settings, see Wikibase.default.php under 'entitySearch'
	 * @param WebRequest|null $request Web request context
	 * @param CirrusDebugOptions|null $options
	 * @throws \MWException
	 */
	public function __construct(
		LanguageFallbackChainFactory $languageChainFactory,
		EntityIdParser $idParser,
		Language $userLang,
		array $contentModelMap,
		array $settings,
		WebRequest $request = null,
		CirrusDebugOptions $options = null
	) {
		$this->languageChainFactory = $languageChainFactory;
		$this->idParser = $idParser;
		$this->userLang = $userLang;
		$this->contentModelMap = $contentModelMap;
		$this->settings = $settings;
		$this->request = $request ?: new \FauxRequest();
		$this->debugOptions = $options ?: CirrusDebugOptions::fromRequest( $this->request );
	}

	/**
	 * Produce ES query that matches the arguments.
	 *
	 * @param string $text
	 * @param string $languageCode
	 * @param string $entityType
	 * @param bool $strictLanguage
	 * @param SearchContext $context
	 *
	 * @return AbstractQuery
	 */
	protected function getElasticSearchQuery(
		$text,
		$languageCode,
		$entityType,
		$strictLanguage,
		SearchContext $context
	) {
		$query = new BoolQuery();

		$context->setOriginalSearchTerm( $text );
		// Drop only leading spaces for exact matches, and all spaces for the rest
		$textExact = ltrim( $text );
		$text = trim( $text );
		if ( empty( $this->contentModelMap[$entityType] ) ) {
			$context->setResultsPossible( false );
			$context->addWarning( 'wikibase-search-bad-entity-type', $entityType );
			return $query;
		}

		$labelsFilter = new Match( 'labels_all.prefix', $text );

		$profile = $context->getConfig()
			->getProfileService()
			->loadProfile( self::WIKIBASE_PREFIX_QUERY_BUILDER, self::CONTEXT_WIKIBASE_PREFIX );

		$dismax = new DisMax();
		$dismax->setTieBreaker( 0 );

		$fields = [
			[ "labels.{$languageCode}.near_match", $profile['lang-exact'] ],
			[ "labels.{$languageCode}.near_match_folded", $profile['lang-folded'] ],
		];
		// Fields to which query applies exactly as stated, without trailing space trimming
		$fieldsExact = [];
		if ( $textExact !== $text ) {
			$fields[] =
				[
					"labels.{$languageCode}.prefix",
					$profile['lang-prefix'] * $profile['space-discount'],
				];
			$fieldsExact[] = [ "labels.{$languageCode}.prefix", $profile['lang-prefix'] ];
		} else {
			$fields[] = [ "labels.{$languageCode}.prefix", $profile['lang-prefix'] ];
		}

		$langChain = $this->languageChainFactory->newFromLanguageCode( $languageCode );
		$this->searchLanguageCodes = $langChain->getFetchLanguageCodes();
		if ( !$strictLanguage ) {
			$fields[] = [ "labels_all.near_match_folded", $profile['any'] ];
			$discount = $profile['fallback-discount'];
			foreach ( $this->searchLanguageCodes as $fallbackCode ) {
				if ( $fallbackCode === $languageCode ) {
					continue;
				}
				$weight = $profile['fallback-exact'] * $discount;
				$fields[] = [ "labels.{$fallbackCode}.near_match", $weight ];
				$weight = $profile['fallback-folded'] * $discount;
				$fields[] = [ "labels.{$fallbackCode}.near_match_folded", $weight ];
				$weight = $profile['fallback-prefix'] * $discount;
				if ( $textExact !== $text ) {
					$fields[] = [
						"labels.{$fallbackCode}.prefix",
						$weight * $profile['space-discount']
					];
					$fieldsExact[] = [ "labels.{$fallbackCode}.prefix", $weight ];
				} else {
					$fields[] = [ "labels.{$fallbackCode}.prefix", $weight ];
				}
				$discount *= $profile['fallback-discount'];
			}
		}

		foreach ( $fields as $field ) {
			$dismax->addQuery( EntitySearchUtils::makeConstScoreQuery( $field[0], $field[1], $text ) );
		}

		foreach ( $fieldsExact as $field ) {
			$dismax->addQuery( EntitySearchUtils::makeConstScoreQuery( $field[0], $field[1], $textExact ) );
		}

		$labelsQuery = new BoolQuery();
		$labelsQuery->addFilter( $labelsFilter );
		$labelsQuery->addShould( $dismax );
		$titleMatch = new Term( [ 'title.keyword' => EntitySearchUtils::normalizeId( $text, $this->idParser ) ] );

		// Match either labels or exact match to title
		$query->addShould( $labelsQuery );
		$query->addShould( $titleMatch );
		$query->setMinimumShouldMatch( 1 );

		// Filter to fetch only given entity type
		$query->addFilter( new Term( [ 'content_model' => $this->contentModelMap[$entityType] ] ) );

		return $query;
	}

	/**
	 * @param string $text
	 * @param string $languageCode
	 * @param string $entityType
	 * @param int $limit
	 * @param bool $strictLanguage
	 *
	 * @return TermSearchResult[]
	 */
	public function getRankedSearchResults(
		$text,
		$languageCode,
		$entityType,
		$limit,
		$strictLanguage
	) {
		$searcher = new WikibasePrefixSearcher( 0, $limit, $this->debugOptions );
		$query = $this->getElasticSearchQuery( $text, $languageCode, $entityType, $strictLanguage,
				$searcher->getSearchContext() );

		$searcher->setResultsType( new ElasticTermResult(
			$this->idParser,
			$this->searchLanguageCodes,
			$this->languageChainFactory->newFromLanguage( $this->userLang )
		) );

		$searcher->getSearchContext()->setProfileContext( self::CONTEXT_WIKIBASE_PREFIX );
		$result = $searcher->performSearch( $query );

		// FIXME: this is a hack, we need to return Status upstream instead
		foreach ( $result->getErrors() as $error ) {
			wfLogWarning( json_encode( $error ) );
		}

		if ( $result->isOK() ) {
			$result = $result->getValue();
		} else {
			$result = [];
		}

		if ( $searcher->isReturnRaw() ) {
			$result = $searcher->processRawReturn( $result, $this->request );
		}

		return $result;
	}

	/**
	 * Determine from the classpath which elastic version we
	 * aim to be compatible with.
	 * @return int
	 */
	public static function getExpectedElasticMajorVersion() {
		if ( class_exists( '\Elastica\Task' ) ) {
			return 6;
		}

		return 5;
	}

}

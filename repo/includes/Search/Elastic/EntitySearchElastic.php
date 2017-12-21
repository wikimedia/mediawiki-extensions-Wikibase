<?php

namespace Wikibase\Repo\Search\Elastic;

use CirrusSearch\Search\SearchContext;
use Elastica\Query\AbstractQuery;
use Elastica\Query\BoolQuery;
use Elastica\Query\ConstantScore;
use Elastica\Query\DisMax;
use Elastica\Query\Match;
use Elastica\Query\Term;
use Language;
use WebRequest;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Repo\Api\EntitySearchHelper;

/**
 * Entity search implementation using ElasticSearch.
 * Requires CirrusSearch extension and $wgEntitySearchUseCirrus to be on.
 *
 * @license GPL-2.0+
 * @author Stas Malyshev
 */
class EntitySearchElastic implements EntitySearchHelper {

	/**
	 * Default rescore profile
	 */
	const DEFAULT_RESCORE_PROFILE = 'wikibase_prefix';

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
	 * Should we return raw result?
	 * Used for testing.
	 * @var boolean
	 */
	private $returnResult;

	/**
	 * @param LanguageFallbackChainFactory $languageChainFactory
	 * @param EntityIdParser $idParser
	 * @param Language $userLang
	 * @param array $contentModelMap Maps entity type => content model name
	 * @param array $settings Search settings, see Wikibase.default.php under 'entitySearch'
	 */
	public function __construct(
		LanguageFallbackChainFactory $languageChainFactory,
		EntityIdParser $idParser,
		Language $userLang,
		array $contentModelMap,
		array $settings
	) {
		$this->languageChainFactory = $languageChainFactory;
		$this->idParser = $idParser;
		$this->userLang = $userLang;
		$this->contentModelMap = $contentModelMap;
		$this->settings = $settings;
		$this->request = new \FauxRequest();
	}

	/**
	 * Set web request context.
	 * @param WebRequest $request
	 */
	public function setRequest( WebRequest $request ) {
		$this->request = $request;
	}

	/**
	 * Load specific label scoring profile.
	 * @param string $profile
	 * @return array|null Profile or null if none found.
	 */
	protected function loadProfile( $profile ) {
		if ( empty( $this->settings['prefixSearchProfiles'][$profile] ) ) {
			return null;
		}
		return $this->settings['prefixSearchProfiles'][$profile];
	}

	/**
	 * Produce ES query that matches the arguments.
	 *
	 * @param $text
	 * @param $languageCode
	 * @param $entityType
	 * @param $strictLanguage
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

		$profileName = $this->request->getVal( 'cirrusWBProfile', $this->settings['defaultPrefixProfile'] );
		$profile = $this->loadProfile( $profileName );
		if ( !$profile ) {
			$context->setResultsPossible( false );
			$context->addWarning( 'wikibase-search-bad-profile-name', $profileName );
			return $query;
		}

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
			$dismax->addQuery( $this->makeConstScoreQuery( $field[0], $field[1], $text ) );
		}

		foreach ( $fieldsExact as $field ) {
			$dismax->addQuery( $this->makeConstScoreQuery( $field[0], $field[1], $textExact ) );
		}

		$labelsQuery = new BoolQuery();
		$labelsQuery->addFilter( $labelsFilter );
		$labelsQuery->addShould( $dismax );
		$titleMatch = new Term( [ 'title.keyword' => $this->normalizeId( $text ) ] );

		// Match either labels or exact match to title
		$query->addShould( $labelsQuery );
		$query->addShould( $titleMatch );
		$query->setMinimumShouldMatch( 1 );

		// Filter to fetch only given entity type
		$query->addFilter( new Term( [ 'content_model' => $this->contentModelMap[$entityType] ] ) );

		return $query;
	}

	/**
	 * Create constant score query for a field.
	 * @param string $field
	 * @param string|double $boost
	 * @param string $text
	 * @return ConstantScore
	 */
	private function makeConstScoreQuery( $field, $boost, $text ) {
		$csquery = new ConstantScore();
		$csquery->setFilter( new Match( $field, $text ) );
		$csquery->setBoost( $boost );
		return $csquery;
	}

	/**
	 * Get suitable rescore profile.
	 * If internal config has non, return just the name and let RescoureBuilder handle it.
	 * @return string|array
	 */
	private function getRescoreProfile() {

		$rescoreProfile = $this->request->getVal( 'cirrusRescoreProfile' );
		if ( !$rescoreProfile && isset( $this->settings['defaultPrefixRescoreProfile'] ) ) {
			$rescoreProfile = $this->settings['defaultPrefixRescoreProfile'];
		}
		if ( !$rescoreProfile ) {
			$rescoreProfile = self::DEFAULT_RESCORE_PROFILE;
		}
		if ( isset( $this->settings['rescoreProfiles'][$rescoreProfile] ) ) {
			return $this->settings['rescoreProfiles'][$rescoreProfile];
		}
		return $rescoreProfile;
	}

	/**
	 * Parse entity ID or return null
	 * @param $text
	 * @return null|\Wikibase\DataModel\Entity\EntityId
	 */
	private function parseOrNull( $text ) {
		try {
			$id = $this->idParser->parse( $text );
		} catch ( EntityIdParsingException $ex ) {
			return null;
		}
		return $id;
	}

	/**
	 * If the text looks like ID, normalize it to ID title
	 * Cases handled:
	 * - q42
	 * - (q42)
	 * - leading/trailing spaces
	 * - http://www.wikidata.org/entity/Q42
	 * @param string $text
	 * @return string Normalized ID or original string
	 */
	private function normalizeId( $text ) {
		// TODO: this is a bit hacky, better way would be to make the field case-insensitive
		// or add new subfiled which is case-insensitive
		$text = strtoupper( str_replace( [ '(', ')' ], '', trim( $text ) ) );
		$id = $this->parseOrNull( $text );
		if ( $id ) {
			return $id->getSerialization();
		}
		if ( preg_match( '/\b(\w+)$/', $text, $matches ) && $matches[1] ) {
			$id = $this->parseOrNull( $matches[1] );
			if ( $id ) {
				return $id->getSerialization();
			}
		}
		return $text;
	}

	/**
	 * @param bool $returnResult
	 */
	public function setReturnResult( $returnResult ) {
		$this->returnResult = $returnResult;
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
		$searcher = new WikibasePrefixSearcher( 0, $limit );
		$query = $this->getElasticSearchQuery( $text, $languageCode, $entityType, $strictLanguage,
				$searcher->getSearchContext() );

		$searcher->setResultsType( new ElasticTermResult(
			$this->idParser,
			$this->searchLanguageCodes,
			$this->languageChainFactory->newFromLanguage( $this->userLang )
		) );

		$searcher->setOptionsFromRequest( $this->request );
		$searcher->setRescoreProfile( $this->getRescoreProfile() );

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
			$result = $searcher->processRawReturn( $result, $this->request, !$this->returnResult );
		}

		return $result;
	}

}

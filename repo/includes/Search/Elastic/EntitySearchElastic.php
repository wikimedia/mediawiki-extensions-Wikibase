<?php

namespace Wikibase\Repo\Search\Elastic;

use CirrusSearch\Search\SearchContext;
use Elastica\Query\AbstractQuery;
use Elastica\Query\BoolQuery;
use Elastica\Query\Match;
use Elastica\Query\MultiMatch;
use Elastica\Query\Term;
use Language;
use RequestContext;
use Status;
use WebRequest;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Repo\Api\EntitySearchHelper;

/**
 * Entity search implementation using ElasticSearch.
 * Requires CirrusSearch extension and $wgEntitySearchUseCirrus to be on.
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
	 * @var \WebRequest
	 */
	private $request;

	/**
	 * List of fallback codes for search language
	 * @var string[]
	 */
	private $searchLanguageCodes;

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
	 * @param WebRequest $request
	 * @param array $settings Search settings, see Wikibase.default.php under 'entitySearch'
	 */
	public function __construct(
		LanguageFallbackChainFactory $languageChainFactory,
		EntityIdParser $idParser,
		Language $userLang,
		array $contentModelMap,
		WebRequest $request,
		array $settings
	) {
		$this->languageChainFactory = $languageChainFactory;
		$this->idParser = $idParser;
		$this->contentModelMap = $contentModelMap;
		$this->request = $request;
		$this->settings = $settings;
		$this->userLang = $userLang;
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
	 * @param $text
	 * @param $languageCode
	 * @param $entityType
	 * @param $strictLanguage
	 * @param SearchContext $context
	 * @return AbstractQuery
	 */
	protected function getElasticSearchQuery( $text, $languageCode, $entityType, $strictLanguage,
	                                          SearchContext $context ) {
		$query = new BoolQuery();

		if ( empty( $this->contentModelMap[$entityType] ) ) {
			$context->setResultsPossible( false );
			$context->addWarning( 'wb-search-bad-entity-type', $entityType );
			return $query;
		}

		$labelsFilter = new Match( 'labels_all.prefix', $text );

		$labelsMulti = new MultiMatch();
		$labelsMulti->setType( 'best_fields' );
		$labelsMulti->setTieBreaker( 0 );
		$labelsMulti->setQuery( $text );

		$profileName = $this->request->getVal( 'cirrusWBProfile', $this->settings['defaultPrefixProfile'] );
		$profile = $this->loadProfile( $profileName );
		if ( !$profile ) {
			$context->setResultsPossible( false );
			$context->addWarning( 'wb-search-bad-profile-name', $profileName );
			return $query;
		}

		$fields = [
			"labels.{$languageCode}.near_match^{$profile['lang-exact']}",
			"labels.{$languageCode}.near_match_folded^{$profile['lang-folded']}",
			"labels.{$languageCode}.prefix^{$profile['lang-prefix']}",
		];

		$langChain = $this->languageChainFactory->newFromLanguageCode( $languageCode );
		$this->searchLanguageCodes = $langChain->getFetchLanguageCodes();
		if ( !$strictLanguage ) {
			$fields[] = "labels_all.near_match^{$profile['any']}";
			$discount = 1;
			foreach ( $this->searchLanguageCodes as $fallbackCode ) {
				if ( $fallbackCode === $languageCode ) {
					continue;
				}
				$weight = $profile['fallback-exact'] * $discount;
				$fields[] = "labels.{$fallbackCode}.near_match^$weight";
				$weight = $profile['fallback-folded'] * $discount;
				$fields[] = "labels.{$fallbackCode}.near_match_folded^$weight";
				$weight = $profile['fallback-prefix'] * $discount;
				$fields[] = "labels.{$fallbackCode}.prefix^$weight";
				$discount *= $profile['fallback-discount'];
			}
		}
		$labelsMulti->setFields( $fields );

		$labelsQuery = new BoolQuery();
		$labelsQuery->addFilter( $labelsFilter );
		if ( $strictLanguage ) {
			$labelsQuery->addMust( $labelsMulti );
		} else {
			$labelsQuery->addShould( $labelsMulti );
		}
		$titleMatch = new Term( [ 'title.keyword' => $text ] );

		// Match either labels or exact match to title
		$query->addShould( $labelsQuery );
		$query->addShould( $titleMatch );
		$query->setMinimumShouldMatch( 1 );

		// Filter to fetch only given entity type
		$query->addFilter( new Term( [ 'content_model' => $this->contentModelMap[$entityType] ] ) );

		return $query;
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
		if ( $this->settings['rescoreProfiles'][$rescoreProfile] ) {
			return $this->settings['rescoreProfiles'][$rescoreProfile];
		}
		return $rescoreProfile;
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
	 * @return TermSearchResult[]
	 */
	public function getRankedSearchResults( $text, $languageCode, $entityType, $limit,
	                                        $strictLanguage ) {
		$searcher = new WikibasePrefixSearcher( 0, $limit );
		$query = $this->getElasticSearchQuery( $text, $languageCode, $entityType, $strictLanguage,
				$searcher->getSearchContext() );

		$searcher->setResultsType( new ElasticTermResult(
			$this->idParser,
			$this->searchLanguageCodes,
			$this->languageChainFactory->newFromLanguage( $this->userLang )->getFetchLanguageCodes()
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

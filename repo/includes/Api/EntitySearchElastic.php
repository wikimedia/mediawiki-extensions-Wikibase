<?php
namespace Wikibase\Repo\Api;

use Elastica\Query\BoolQuery;
use Elastica\Query\Match;
use Elastica\Query\MultiMatch;
use Elastica\Query\Term;
use RequestContext;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Interactors\TermSearchResult;

class EntitySearchElastic implements EntitySearcher {

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageChainFactory;
	/**
	 * @var LabelDescriptionLookup
	 */
	private $labelDescriptionLookup;

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

	public function __construct(
		LanguageFallbackChainFactory $languageChainFactory,
		EntityIdParser $idParser,
		LabelDescriptionLookup $labelDescriptionLookup,
		$contentModelMap
	) {
		$this->languageChainFactory = $languageChainFactory;
		$this->idParser = $idParser;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
		$this->contentModelMap = $contentModelMap;
		$this->request = RequestContext::getMain()->getRequest();
	}

	/**
	 * Load specific scoring profile.
	 * @param $profile
	 * @return mixed
	 */
	protected function loadProfile( $profile ) {
		global $wgEntityPrefixSearchProfiles;
		if ( empty( $wgEntityPrefixSearchProfiles[$profile] ) ) {
			// TODO: log message?
			$profile = 'default';
		}
		return $wgEntityPrefixSearchProfiles[$profile];
	}

	/**
	 * @param string $text
	 * @param string $languageCode
	 * @param string $entityType
	 * @param int $limit
	 * @param bool $strictLanguage
	 * @return TermSearchResult[]
	 * @throws \Exception
	 */
	public function getRankedSearchResults( $text, $languageCode, $entityType, $limit,
	                                        $strictLanguage ) {
		global $wgEntitySearchDefaultProfile;
		if ( empty( $this->contentModelMap[$entityType] ) ) {
			throw new \Exception( "Unknown entity type $entityType" );
		}

		$query = new BoolQuery();

		$labelsFilter = new Match( 'labels_all.prefix', $text );

		$labelsMulti = new MultiMatch();
		$labelsMulti->setType( 'best_fields' );
		$labelsMulti->setTieBreaker( 0 );
		$labelsMulti->setQuery( $text );

		$profile = $this->request->getVal( 'cirrusWBProfile', $wgEntitySearchDefaultProfile );
		$profile = $this->loadProfile( $profile );

		$fields = [
			"labels_all.near_match^{$profile['any']}",
			"labels_{$languageCode}.near_match^{$profile['lang-exact']}",
			"labels_{$languageCode}.near_match_folded^{$profile['lang-folded']}",
			"labels_{$languageCode}.prefix^{$profile['lang-prefix']}",
		];

		if ( !$strictLanguage ) {
			$langChain = $this->languageChainFactory->newFromLanguageCode( $languageCode );
			$discount = 1;
			$fallbackCodes = $langChain->getFetchLanguageCodes();
			foreach ( $fallbackCodes as $fallbackCode ) {
				if ( $fallbackCode === $languageCode ) {
					continue;
				}
				$weight = $profile['fallback-exact'] * $discount;
				$fields[] = "labels_{$fallbackCode}.near_match^$weight";
				$weight = $profile['fallback-folded'] * $discount;
				$fields[] = "labels_{$fallbackCode}.near_match_folded^$weight";
				$weight = $profile['fallback-prefix'] * $discount;
				$fields[] = "labels_{$fallbackCode}.prefix^$weight";
				$discount *= $profile['fallback-discount'];
			}
		} else {
			$fallbackCodes = [ $languageCode ];
		}

		$labelsMulti->setFields( $fields );

		$labelsQuery = new BoolQuery();
		$labelsQuery->addFilter( $labelsFilter );
		$labelsQuery->addShould( $labelsMulti );

		$titleMatch = new Term( [ 'title.keyword' => $text ] );

		// Match either labels or exact match to title
		$query->addShould( $labelsQuery );
		$query->addShould( $titleMatch );
		$query->setMinimumNumberShouldMatch( 1 );

		// Filter to fetch only given entity type
		$query->addFilter( new Term( [ 'content_model' => $this->contentModelMap[$entityType] ] ) );

		$searcher = new WikibasePrefixSearcher( 0, $limit, $query );
		$searcher->setResultsType( new ElasticTermResult(
			$this->idParser,
			$this->labelDescriptionLookup,
			$fallbackCodes
		) );

		$dumpQuery = $this->request && $this->request->getVal( 'cirrusDumpQuery' ) !== null;
		$searcher->setReturnQuery( $dumpQuery );
		$dumpResult = $this->request && $this->request->getVal( 'cirrusDumpResult' ) !== null;
		$searcher->setDumpResult( $dumpResult );

		$result = $searcher->performSearch();
		if ( $searcher->isReturnRaw() ) {
			$header = 'Content-type: application/json; charset=UTF-8';
			$result = json_encode( $result, JSON_PRETTY_PRINT );
			RequestContext::getMain()->getOutput()->disable();
			if ( $header !== null ) {
				$this->request->response()->header( $header );
			}
			echo $result;
			exit();
		}

		return $result;
	}

}

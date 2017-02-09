<?php

namespace Wikibase\Repo\Search\Elastic;

use CirrusSearch\Search\ResultsType;
use CirrusSearch\Search\SearchContext;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Term\Term;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\Interactors\TermSearchResult;

/**
 * This result type implements the result for searching
 * a Wikibase entity by its label or alias.
 */
class ElasticTermResult implements ResultsType {

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * List of language codes in the search fallback chain, the first
	 * is the preferred language.
	 * @var string[]
	 */
	private $searchLanguageCodes;

	/**
	 * Display fallback chain.
	 * @var LanguageFallbackChain
	 */
	private $fallbackChain;

	/**
	 * Display fallback chain.
	 * @var LanguageFallbackChain
	 */
	private $fallbackChain;

	/**
	 * ElasticTermResult constructor.
	 * @param EntityIdParser $idParser
	 * @param string[] $searchLanguageCodes Language fallback chain for search
	 * @param LanguageFallbackChain $displayFallbackChain Fallback chain for display
	 */
	public function __construct( EntityIdParser $idParser,
	                             array $searchLanguageCodes,
	                             LanguageFallbackChain $displayFallbackChain
	) {
		$this->idParser = $idParser;
		$this->searchLanguageCodes = $searchLanguageCodes;
		$this->fallbackChain = $displayFallbackChain;
	}

	/**
	 * Get the source filtering to be used loading the result.
	 *
	 * @return false|string|array corresponding to Elasticsearch source filtering syntax
	 */
	public function getSourceFiltering() {
		$fields = [ 'namespace', 'title' ];
		foreach ( $this->fallbackChain->getFetchLanguageCodes() as $code ) {
			$fields[] = "labels.$code";
			$fields[] = "descriptions.$code";
		}
		return $fields;
	}

	/**
	 * Get the fields to load.  Most of the time we'll use source filtering instead but
	 * some fields aren't part of the source.
	 *
	 * @return string[]
	 */
	public function getFields() {
		return [];
	}

	/**
	 * ES5 variant of getFields.
	 * @return string[]
	 */
	public function getStoredFields() {
		return [];
	}

	/**
	 * Get the highlighting configuration.
	 *
	 * @param array $highlightSource configuration for how to highlight the source.
	 *  Empty if source should be ignored.
	 * @return array|null highlighting configuration for elasticsearch
	 */
	public function getHighlightingConfiguration( array $highlightSource ) {
		$config = [
			'pre_tags' => [ '' ],
			'post_tags' => [ '' ],
			'fields' => [],
		];
		$config['fields']['title'] = [
			'type' => 'experimental',
			'fragmenter' => "none",
			'number_of_fragments' => 0,
			'matched_fields' => [ 'title.keyword' ]
		];
		foreach ( $this->searchLanguageCodes as $code ) {
			$config['fields']["labels.$code.prefix"] = [
				'type' => 'experimental',
				'fragmenter' => "none",
				'number_of_fragments' => 0,
				'options' => [ 'skip_if_last_matched' => true ],
			];
		}
		$config['fields']['labels_all.prefix'] = [
			'type' => 'experimental',
			'fragmenter' => "none",
			'number_of_fragments' => 0,
			'options' => [ 'skip_if_last_matched' => true ],
		];

		return $config;
	}

	/**
	 * Locate label for display among the source data, basing on fallback chain.
	 * @param array $sourceData
	 * @param string $field
	 * @return null|Term
	 */
	private function findTermForDisplay( $sourceData, $field ) {
		$labels_data = array_filter( array_map(
			function ( $data ) {
				return !empty( $data[0] ) ? $data[0] : null;
			},
			$sourceData[$field] )
		);

		$preferredValue = $this->fallbackChain->extractPreferredValueOrAny( $labels_data );
		if ( $preferredValue ) {
			return new Term( $preferredValue['language'], $preferredValue['value'] );
		}

		return null;
	}

	/**
	 * Convert search result from ElasticSearch result set to TermSearchResult.
	 * @param SearchContext $context
	 * @param \Elastica\ResultSet $result
	 * @return TermSearchResult[] Set of search results, the types of which vary by implementation.
	 */
	public function transformElasticsearchResult( SearchContext $context, \Elastica\ResultSet $result ) {
		$results = [];
		foreach ( $result->getResults() as $r ) {
			$sourceData = $r->getSource();
			try {
				$entityId = $this->idParser->parse( $sourceData['title'] );
			} catch ( EntityIdParsingException $e ) {
				// Can not parse entity ID - skip it
				continue;
			}

			// Highlight part contains information about what has actually been matched.
			$highlight = $r->getHighlights();
			$matchedTermType = 'label';
			$displayLabel = $this->findTermForDisplay( $sourceData, 'labels' );
			$displayDescription = $this->findTermForDisplay( $sourceData, 'descriptions' );

			if ( !empty( $highlight['title'] ) ) {
				// If we matched title, this means it's a match by ID
				$matchedTermType = 'entityId';
				$matchedTerm = new Term( 'qid', $sourceData['title'] );
			} elseif ( empty( $highlight ) ) {
				// Something went wrong, we don't have any highlighting data
				continue;
			} else {
				// We matched the actual label - find which language it is and
				// whether it's main label (always first) or alias (not first)
				$term = reset( $highlight );
				$term = $term[0]; // Highlighter returns array
				$field = key( $highlight );
				if ( preg_match( '/^labels\.(.+?)\.prefix$/', $field, $match ) ) {
					$langCode = $match[1];
					if ( !empty( $sourceData['labels'][$langCode] ) ) {
						// Primary label always comes first, so if it's not the first one,
						// it's an alias.
						if ( $sourceData['labels'][$langCode][0] !== $term ) {
							$matchedTermType = 'alias';
						}
					}
				} else {
					// This is weird since we didn't ask to match anything else,
					// but we'll return it anyway for debugging.
					$langCode = 'unknown';
				}
				$matchedTerm = new Term( $langCode, $term );
			}

			if ( !$displayLabel ) {
				// This should not happen, but just in case, it's better to return something
				$displayLabel = $matchedTerm;
			}

			$results[$entityId->getSerialization()] = new TermSearchResult(
				$matchedTerm, $matchedTermType, $entityId, $displayLabel,
				$displayDescription
			);
		}
		return $results;

	}

	/**
	 * @return TermSearchResult[] Empty set of search results
	 */
	public function createEmptyResult() {
		return [];
	}

}

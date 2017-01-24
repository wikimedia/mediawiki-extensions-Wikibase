<?php

namespace Wikibase\Repo\Search\Elastic;

use CirrusSearch\Search\ResultsType;
use CirrusSearch\Search\SearchContext;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\TermSearchResult;

/**
 * This result type implements the result for searching
 * a Wikibase entity by its label or alias.
 */
class ElasticTermResult implements ResultsType {
	/**
	 * @var LabelDescriptionLookup
	 */
	private $labelDescriptionLookup;

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
	 * List of language codes in the display fallback chain, the first
	 * is the preferred language.
	 * @var string[]
	 */
	private $displayLanguageCodes;

	/**
	 * ElasticTermResult constructor.
	 * @param EntityIdParser $idParser
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 * @param string[] $searchLanguageCodes Language fallback chain for search
	 * @param string[] $displayLanguageCodes Language fallback chain for display
	 */
	public function __construct( EntityIdParser $idParser,
	                             LabelDescriptionLookup $labelDescriptionLookup,
	                             array $searchLanguageCodes,
	                             array $displayLanguageCodes
	) {
		$this->idParser = $idParser;
		$this->searchLanguageCodes = $searchLanguageCodes;
		$this->displayLanguageCodes = $displayLanguageCodes;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
	}

	/**
	 * Get the source filtering to be used loading the result.
	 *
	 * @return false|string|array corresponding to Elasticsearch source filtering syntax
	 */
	public function getSourceFiltering() {
		$fields = [ 'namespace', 'title' ];
		foreach ( $this->displayLanguageCodes as $code ) {
			$fields[] = "labels.$code";
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
	 * @return null|Term
	 */
	private function findLabelForDisplay( $sourceData ) {
		foreach ( $this->displayLanguageCodes as $code ) {
			if ( !empty( $sourceData['labels'][$code] ) && $sourceData['labels'][$code][0] !== '' ) {
				return new Term( $code, $sourceData['labels'][$code][0] );
			}
		}
		return null;
	}

	/**
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

			$highlight = $r->getHighlights();
			$matchedTermType = 'label';
			$displayLabel = $this->findLabelForDisplay( $sourceData );

			if ( !empty( $highlight['title'] ) ) {
				$matchedTermType = 'entityId';
				$matchedTerm = new Term( 'qid', $sourceData['title'] );
			} elseif ( empty( $highlight ) ) {
				// Something went wrong, we don't have any highlighting data
				continue;
			} else {
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
					$langCode = 'unknown';
				}
				$matchedTerm = new Term( $langCode, $term );
			}

			if ( !$displayLabel ) {
				// This should not happen, but just in case, it's better to return something
				$displayLabel = $matchedTerm;
			}
			// TODO: eventually this will be fetched from ES.
			$displayDescription = $this->labelDescriptionLookup->getDescription( $entityId );

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

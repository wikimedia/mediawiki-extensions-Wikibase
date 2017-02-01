<?php
namespace Wikibase\Repo\Api;

use CirrusSearch\Search\ResultsType;
use CirrusSearch\Search\SearchContext;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Lib\Interactors\TermSearchResult;

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
	 * List of language codes in the fallback chain, the first
	 * is the preferred language.
	 * @var string[]
	 */
	private $languageCodes;

	public function __construct( EntityIdParser $idParser,
	                             LabelDescriptionLookup $labelDescriptionLookup, $languageCodes ) {
		$this->idParser = $idParser;
		$this->languageCodes = $languageCodes;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
	}

	/**
	 * Get the source filtering to be used loading the result.
	 *
	 * @return false|string|array corresponding to Elasticsearch source filtering syntax
	 */
	function getSourceFiltering() {
		$fields = [ 'namespace', 'title' ];
		foreach ( $this->languageCodes as $code ) {
			$fields[] = "labels.$code";
		}
		return $fields;
	}

	/**
	 * Get the fields to load.  Most of the time we'll use source filtering instead but
	 * some fields aren't part of the source.
	 *
	 * @return false|string|array corresponding to Elasticsearch fields syntax
	 */
	function getFields() {
		return false;
	}

	/**
	 * Get the highlighting configuration.
	 *
	 * @param array $highlightSource configuration for how to highlight the source.
	 *  Empty if source should be ignored.
	 * @return array|null highlighting configuration for elasticsearch
	 */
	function getHighlightingConfiguration( array $highlightSource ) {
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
		foreach ( $this->languageCodes as $code ) {
			$config['fields']["labels.$code.prefix"] = [
				'type' => 'experimental',
				'fragmenter' => "none",
				'number_of_fragments' => 0,
				'options' => [ 'skip_if_last_matched' => true ],
			];
		}

		return $config;
	}

	/**
	 * @param SearchContext $context
	 * @param \Elastica\ResultSet $result
	 * @return TermSearchResult[] Set of search results, the types of which vary by implementation.
	 */
	function transformElasticsearchResult( SearchContext $context, \Elastica\ResultSet $result ) {
		$results = [];
		foreach ( $result->getResults() as $r ) {
			$sourceData = $r->getSource();
			try {
				$entityId = $this->idParser->parse( $sourceData['title'] );
			}
			catch ( EntityIdParsingException $e ) {
				// Can not parse entity ID - skip it
				continue;
			}

			$highlight = $r->getHighlights();
			$matchedTermType = 'item';

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
						// Primary label always comes first
						if ( $sourceData['labels'][$langCode][0] !== $term ) {
							$matchedTermType = 'alias';
						}
					}
				} else {
					$langCode = 'unknown';
				}
				$matchedTerm = new Term( $langCode, $term );
			}

			$displayLabel = $this->labelDescriptionLookup->getLabel( $entityId );
			$displayDescription = $this->labelDescriptionLookup->getDescription( $entityId );

			// FIXME: use proper terms, requires highlighting setup

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
	function createEmptyResult() {
		return [];
	}
}
<?php
namespace Wikibase\Repo\Search\Elastic;

use CirrusSearch\Search\Result;
use CirrusSearch\Searcher;
use HtmlArmor;
use Wikibase\LanguageFallbackChain;

/**
 * Single result for entity search.
 */
class EntityResult extends Result {
	/**
	 * @var Result
	 */
	private $cirrusResult;

	/**
	 * Label data with highlighting.
	 * ['language' => LANG, 'value' => TEXT]
	 * @var string[]
	 */
	private $labelHighlightedData;
	/**
	 * Raw label data from source.
	 * ['language' => LANG, 'value' => TEXT]
	 * @var string[]
	 */
	private $labelData;
	/**
	 * Description data with highlighting.
	 * ['language' => LANG, 'value' => TEXT]
	 * @var string[]
	 */
	private $descriptionData;
	/**
	 * Description data from source.
	 * ['language' => LANG, 'value' => TEXT]
	 * @var string[]
	 */
	private $descriptionHighlightedData;

	/**
	 * @param string $displayLanguage
	 * @param LanguageFallbackChain $displayFallbackChain
	 * @param \Elastica\Result $result
	 */
	public function __construct( $displayLanguage, LanguageFallbackChain $displayFallbackChain,
	                             $result ) {
		// Let Cirrus\Result class handle the boring stuff
		parent::__construct( null, $result );
		// FIXME: null is not nice, but Result doesn't really need it...
		// Think how to fix this.

		$sourceData = $result->getSource();
		$highlightData = $result->getHighlights();

		$this->labelHighlightedData = $this->getHighlightOrField(
			'labels', $displayLanguage, $displayFallbackChain,
			$highlightData, $sourceData );
		$this->labelData = $this->getSourceField(
			'labels', $displayLanguage, $displayFallbackChain, $sourceData );

		$this->descriptionHighlightedData = $this->getHighlightOrField(
			'descriptions', $displayLanguage, $displayFallbackChain,
			$highlightData, $sourceData );
		$this->descriptionData = $this->getSourceField(
			'descriptions', $displayLanguage, $displayFallbackChain, $sourceData );
	}

	/**
	 * Extract field value from highlighting or source data.
	 * @param string $field
	 * @param string $displayLanguage
	 * @param LanguageFallbackChain $displayFallbackChain
	 * @param array $highlightData
	 * @param array $sourceData
	 * @return array [ $language, $text ] or [null, null] if nothing found
	 */
	private function getHighlightOrField( $field, $displayLanguage,
	                                      LanguageFallbackChain $displayFallbackChain,
	                                      $highlightData, $sourceData ) {
		// Try highlights first
		if ( !empty( $highlightData["{$field}.{$displayLanguage}.plain"] ) ) {
			return [
				'language' => $displayLanguage,
				'value' => new HtmlArmor( $this->processHighlighting( $highlightData["{$field}.{$displayLanguage}.plain"][0] ) )
			];
		}
		// If that failed, try source data
		return $this->getSourceField( $field, $displayLanguage, $displayFallbackChain, $sourceData );
	}

	/**
	 * Get data from source fields
	 * @param $field
	 * @param $displayLanguage
	 * @param $displayFallbackChain
	 * @param $sourceData
	 * @return array
	 */
	private function getSourceField( $field, $displayLanguage,
	                                 LanguageFallbackChain $displayFallbackChain, $sourceData ) {
		// If that failed, try source data
		$term = ElasticTermResult::findTermForDisplay( $sourceData, $field, $displayFallbackChain );
		if ( $term ) {
			return [ 'language' => $term->getLanguageCode(), 'value' => $term->getText() ];
		}
		// OK, we don't have much here
		return [ 'language' => $displayLanguage, 'value' => '' ];
	}

	/**
	 * Process highlighted string from search results.
	 * @param string $snippet
	 * @return string Highlighted and HTML-protected string
	 */
	private function processHighlighting( $snippet ) {
		return strtr( htmlspecialchars( $snippet ), [
			Searcher::HIGHLIGHT_PRE_MARKER => Searcher::HIGHLIGHT_PRE,
			Searcher::HIGHLIGHT_POST_MARKER => Searcher::HIGHLIGHT_POST
		] );
	}

	/**
	 * @return string[]
	 */
	public function getLabelData() {
		return $this->labelData;
	}

	/**
	 * @return string[]
	 */
	public function getDescriptionData() {
		return $this->descriptionData;
	}

	/**
	 * @return string[]
	 */
	public function getLabelHighlightedData() {
		return $this->labelHighlightedData;
	}

	/**
	 * @return string[]
	 */
	public function getDescriptionHighlightedData() {
		return $this->descriptionHighlightedData;
	}

	/**
	 * Return text language
	 * @return string
	 */
	public function getTextLanguage() {
		return $this->descriptionData['language'];
	}

	/**
	 * @return string
	 */
	public function getTitleSnippet() {
		return HtmlArmor::getHtml( $this->labelHighlightedData['value'] );
	}

	/**
	 * @param array $terms
	 * @return string
	 */
	public function getTextSnippet( $terms ) {
		return HtmlArmor::getHtml( $this->descriptionHighlightedData['value'] );
	}
}

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
	 * Did we capture actual match in display of
	 * label or description?
	 * @var boolean
	 */
	private $haveMatch;
	/**
	 * Extra display field for match.
	 * ['language' => LANG, 'value' => TEXT]
	 * @var string[]
	 */
	private $extraDisplay;
	/**
	 * Display language
	 * @var string
	 */
	private $displayLanguage;
	/**
	 * Original source data
	 * @var array
	 */
	private $sourceData;

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
		$this->displayLanguage = $displayLanguage;

		$this->sourceData = $result->getSource();
		$highlightData = $result->getHighlights();

		$this->labelHighlightedData = $this->getHighlightOrField(
			'labels', $displayLanguage, $displayFallbackChain,
			$highlightData, $this->sourceData );
		$this->labelData = $this->getSourceField(
			'labels', $displayLanguage, $displayFallbackChain, $this->sourceData );

		$this->descriptionHighlightedData = $this->getHighlightOrField(
			'descriptions', $displayLanguage, $displayFallbackChain,
			$highlightData, $this->sourceData );
		$this->descriptionData = $this->getSourceField(
			'descriptions', $displayLanguage, $displayFallbackChain, $this->sourceData );

		if ( !$this->haveMatch ) {
			reset( $highlightData );
			$key = key( $highlightData );
			if ( $key && preg_match( '/^\w+\.([^.]+)\.plain$/', $key, $match ) ) {
				$this->extraDisplay = [
					'language' => $match[1],
					'value' => new HtmlArmor( $this->processHighlighting( $highlightData[$key][0] ) )
				];
			}
		}
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
			$highlightData, $sourceData
	) {
		// Try highlights first, if we have needed language there, use highlighted data
		if ( !empty( $highlightData["{$field}.{$displayLanguage}.plain"] ) ) {
			$this->haveMatch = true;
			return [
				'language' => $displayLanguage,
				'value' => new HtmlArmor( $this->processHighlighting( $highlightData["{$field}.{$displayLanguage}.plain"][0] ) )
			];
		}
		// If that failed, try source data
		$source = $this->getSourceField( $field, $displayLanguage, $displayFallbackChain, $sourceData );
		// But if we actually have highlight for this one, use it!
		if ( $source && !empty( $highlightData["{$field}.{$source['language']}.plain"] ) ) {
			$this->haveMatch = true;
			return [
				'language' => $source['language'],
				'value' => new HtmlArmor( $this->processHighlighting( $highlightData["{$field}.{$source['language']}.plain"][0] ) )
			];
		}
		return $source;
	}

	/**
	 * Get data from source fields, using fallback chain if necessary.
	 * @param string $field Field in source data where we're looking.
	 *                      The field will contain subfield by language names.
	 * @param string $displayLanguage
	 * @param LanguageFallbackChain $displayFallbackChain
	 * @param array $sourceData The source data as returned by Elastic.
	 * @return array
	 */
	private function getSourceField( $field, $displayLanguage,
			LanguageFallbackChain $displayFallbackChain,
			$sourceData
	) {
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
	 * @return string Highlighted and HTML-encoded string
	 */
	private function processHighlighting( $snippet ) {
		return strtr( htmlspecialchars( $snippet ), [
			Searcher::HIGHLIGHT_PRE_MARKER => Searcher::HIGHLIGHT_PRE,
			Searcher::HIGHLIGHT_POST_MARKER => Searcher::HIGHLIGHT_POST
		] );
	}

	/**
	 * @return string[] ['language' => LANG, 'value' => TEXT]
	 */
	public function getLabelData() {
		return $this->labelData;
	}

	/**
	 * @return string[] ['language' => LANG, 'value' => TEXT]
	 */
	public function getDescriptionData() {
		return $this->descriptionData;
	}

	/**
	 * @return string[] ['language' => LANG, 'value' => TEXT]
	 */
	public function getLabelHighlightedData() {
		return $this->labelHighlightedData;
	}

	/**
	 * @return string[] ['language' => LANG, 'value' => TEXT]
	 */
	public function getDescriptionHighlightedData() {
		return $this->descriptionHighlightedData;
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

	/**
	 * @return string[] ['language' => LANG, 'value' => TEXT]
	 */
	public function getExtraDisplay() {
		return $this->extraDisplay;
	}

	/**
	 * Get number of statements
	 * @return int
	 */
	public function getStatementCount() {
		if ( !isset( $this->sourceData['statement_count'] ) ) {
			return 0;
		}
		return (int)$this->sourceData['statement_count'];
	}

	/**
	 * Get number of sitelinks
	 * @return int
	 */
	public function getSitelinkCount() {
		if ( !isset( $this->sourceData['sitelink_count'] ) ) {
			return 0;
		}
		return (int)$this->sourceData['sitelink_count'];
	}

}

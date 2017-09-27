<?php
namespace Wikibase\Repo\Search\Elastic;

use CirrusSearch\Search\Result;
use SearchResult;
use Wikibase\DataModel\Term\Term;
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
	 * @var string
	 */
	private $titleSnippet;
	/**
	 * @var string
	 */
	private $textSnippet;
	/**
	 * @var string
	 */
	private $textLanguage;

	/**
	 * @param string $displayLanguage
	 * @param LanguageFallbackChain $displayFallbackChain
	 * @param \Elastica\Result|false $result
	 */
	public function __construct( $displayLanguage, LanguageFallbackChain $displayFallbackChain,
	                             $result ) {
		// Let Cirrus\Result class handle the boring stuff
		parent::__construct( null, $result );
		// FIXME: null is not nice, but Result doesn't really need it...
		// Think how to fix this.

		$sourceData = $result->getSource();
		$titleTerm = ElasticTermResult::findTermForDisplay( $sourceData, 'labels', $displayFallbackChain );
		if ( $titleTerm ) {
			$this->titleSnippet = $titleTerm->getText();
		}
		$textTerm = ElasticTermResult::findTermForDisplay( $sourceData, 'descriptions', $displayFallbackChain );
		if ( $textTerm ) {
			$this->textSnippet = $textTerm->getText();
			$this->textLanguage = $textTerm->getLanguageCode();
		}
		// TODO: process highlighting!
	}

	/**
	 * @return string
	 */
	public function getTitleSnippet() {
		return $this->titleSnippet;
	}

	/**
	 * @param array $terms
	 * @return string|null
	 */
	public function getTextSnippet( $terms ) {
		return $this->textSnippet;
	}

	/**
	 * Return text language
	 * @return string
	 */
	public function getTextLanguage() {
		return $this->textLanguage;
	}

}

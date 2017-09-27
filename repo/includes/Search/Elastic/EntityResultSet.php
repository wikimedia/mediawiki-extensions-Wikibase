<?php
namespace Wikibase\Repo\Search\Elastic;

use CirrusSearch\Search\ResultSet;
use Wikibase\LanguageFallbackChain;

/**
 * Result set for entity search
 * @package Wikibase\Repo\Search\Elastic
 */
class EntityResultSet extends ResultSet {

	/**
	 * Display fallback chain.
	 * @var LanguageFallbackChain
	 */
	private $fallbackChain;
	/**
	 * Display language code
	 * @var string
	 */
	private $displayLanguage;

	/**
	 * EntityResultSet constructor.
	 * @param string $displayLanguage
	 * @param LanguageFallbackChain $displayFallbackChain
	 * @param \Elastica\ResultSet $result
	 */
	public function __construct( $displayLanguage,
		LanguageFallbackChain $displayFallbackChain,
		\Elastica\ResultSet $result
	) {
		parent::__construct( [], [], $result, false );
		$this->fallbackChain = $displayFallbackChain;
		$this->displayLanguage = $displayLanguage;
	}

	/**
	 * @return bool|EntityResult
	 */
	public function next() {
		$current = parent::nextRawResult();
		if ( $current ) {
			$result = new EntityResult( $this->displayLanguage, $this->fallbackChain, $current );
			$this->augmentResult( $result );
			return $result;
		}
		return false;
	}

}

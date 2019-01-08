<?php
namespace Wikibase\Repo\Search\Elastic;

use CirrusSearch\Search\ResultSet;
use Wikibase\LanguageFallbackChain;

/**
 * Result set for entity search
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
	 * @param string $displayLanguage
	 * @param LanguageFallbackChain $displayFallbackChain
	 * @param \Elastica\ResultSet|null $result
	 */
	public function __construct( $displayLanguage,
		LanguageFallbackChain $displayFallbackChain,
		\Elastica\ResultSet $result = null
	) {
		parent::__construct( false, $result );
		$this->fallbackChain = $displayFallbackChain;
		$this->displayLanguage = $displayLanguage;
	}

	protected function transformOneResult( \Elastica\Result $result ) {
		return new EntityResult( $this->displayLanguage, $this->fallbackChain, $result );
	}

}

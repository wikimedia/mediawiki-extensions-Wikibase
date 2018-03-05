<?php

namespace Wikibase\Lib\Interactors;

use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\PrefetchingTermLookup;
use Wikibase\TermIndex;

/**
 * Class creating TermIndexSearchInteractor instances configured for the particular display language.
 *
 * @license GPL-2.0-or-later
 */
class TermIndexSearchInteractorFactory implements TermSearchInteractorFactory {

	/**
	 * @var TermIndex
	 */
	private $termIndex;

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

	/**
	 * @var PrefetchingTermLookup
	 */
	private $prefetchingTermLookup;

	public function __construct(
		TermIndex $termIndex,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		PrefetchingTermLookup $prefetchingTermLookup
	) {
		$this->termIndex = $termIndex;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->prefetchingTermLookup = $prefetchingTermLookup;
	}

	/**
	 * @param string $displayLanguageCode
	 *
	 * @return TermIndexSearchInteractor
	 */
	public function newInteractor( $displayLanguageCode ) {
		return new TermIndexSearchInteractor(
			$this->termIndex,
			$this->languageFallbackChainFactory,
			$this->prefetchingTermLookup,
			$displayLanguageCode
		);
	}

}

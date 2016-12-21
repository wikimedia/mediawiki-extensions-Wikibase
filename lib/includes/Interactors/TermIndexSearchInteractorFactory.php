<?php

namespace Wikibase\Lib\Interactors;

use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\PrefetchingTermLookup;
use Wikibase\TermIndex;

/**
 * Class creating TermIndexSearchInteractor instances configured for the particular display language.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 */
class TermIndexSearchInteractorFactory implements TermSearchInteractorFactory {

	private $termIndex;

	private $languageFallbackChainFactory;

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
	 * @return TermIndexSearchInteractor
	 */
	public function getInteractor( $displayLanguageCode ) {
		return new TermIndexSearchInteractor(
			$this->termIndex,
			$this->languageFallbackChainFactory,
			$this->prefetchingTermLookup,
			$displayLanguageCode
		);
	}

}

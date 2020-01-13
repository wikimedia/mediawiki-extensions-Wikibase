<?php

namespace Wikibase\Lib\Interactors;

use Wikibase\LanguageFallbackChainFactory;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\TermIndex;

/**
 * Class creating TermIndexSearchInteractor instances configured for the particular display language.
 *
 * @license GPL-2.0-or-later
 */
class MatchingTermsSearchInteractorFactory implements TermSearchInteractorFactory {

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
	 * @return MatchingTermsLookupSearchInteractor
	 */
	public function newInteractor( $displayLanguageCode ) {
		return new MatchingTermsLookupSearchInteractor(
			$this->termIndex,
			$this->languageFallbackChainFactory,
			$this->prefetchingTermLookup,
			$displayLanguageCode
		);
	}

}

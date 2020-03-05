<?php

namespace Wikibase\Lib\Interactors;

use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\MatchingTermsLookup;

/**
 * Class creating TermIndexSearchInteractor instances configured for the particular display language.
 *
 * @license GPL-2.0-or-later
 */
class MatchingTermsSearchInteractorFactory implements TermSearchInteractorFactory {

	/**
	 * @var MatchingTermsLookup
	 */
	private $tmatchingTermsLookup;

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

	/**
	 * @var PrefetchingTermLookup
	 */
	private $prefetchingTermLookup;

	public function __construct(
		MatchingTermsLookup $matchingTermsLookup,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		PrefetchingTermLookup $prefetchingTermLookup
	) {
		$this->tmatchingTermsLookup = $matchingTermsLookup;
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
			$this->tmatchingTermsLookup,
			$this->languageFallbackChainFactory,
			$this->prefetchingTermLookup,
			$displayLanguageCode
		);
	}

}

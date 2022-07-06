<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Store;

use Language;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheFacade;

/**
 * Factory to create a {@link FallbackLabelDescriptionLookup} that also resolves redirects.
 *
 * @license GPL-2.0-or-later
 */
class FallbackLabelDescriptionLookupFactory {

	/** @var LanguageFallbackChainFactory */
	private $languageFallbackChainFactory;
	/** @var RedirectResolvingLatestRevisionLookup */
	private $redirectResolvingRevisionLookup;
	/** @var TermFallbackCacheFacade */
	private $termFallbackCache;
	/** @var TermLookup */
	private $termLookup;
	/** @var TermBuffer|null */
	private $termBuffer;

	/**
	 * $termBuffer will be used to prefetch terms if it is provided;
	 * in that case, $termLookup should be based on it
	 * (they may even be the same object, a {@link PrefetchingTermLookup}).
	 */
	public function __construct(
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		RedirectResolvingLatestRevisionLookup $redirectResolvingLatestRevisionLookup,
		TermFallbackCacheFacade $termFallbackCache,
		TermLookup $termLookup,
		TermBuffer $termBuffer = null
	) {
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->redirectResolvingRevisionLookup = $redirectResolvingLatestRevisionLookup;
		$this->termFallbackCache = $termFallbackCache;
		$this->termLookup = $termLookup;
		$this->termBuffer = $termBuffer;
	}

	/**
	 * Create a new LabelDescriptionLookup in the given language
	 * that applies language fallbacks, resolves redirects,
	 * and has the given entity ID term types (if any) prefetched.
	 *
	 * @param Language $language
	 * @param EntityId[] $entityIds Entity IDs to prefetch terms for, if any.
	 * Only relevant if the factory was constructed with a TermBuffer.
	 * @param string[] $termTypes Term types to prefetch (default: only labels).
	 * One or more TermTypes constants.
	 * @return FallbackLabelDescriptionLookup
	 */
	public function newLabelDescriptionLookup(
		Language $language,
		array $entityIds = [],
		array $termTypes = [ TermTypes::TYPE_LABEL ]
	): FallbackLabelDescriptionLookup {
		$languageFallbackChain = $this->languageFallbackChainFactory->newFromLanguage( $language );

		$labelDescriptionLookup = new CachingFallbackLabelDescriptionLookup(
			$this->termFallbackCache,
			$this->redirectResolvingRevisionLookup,
			new LanguageFallbackLabelDescriptionLookup(
				$this->termLookup,
				$languageFallbackChain
			),
			$languageFallbackChain
		);

		// Optionally prefetch the terms of the entities passed in here
		// ($termLookup is assumed to be based on $termBuffer then)
		if ( $this->termBuffer !== null && $entityIds !== [] ) {
			$languages = $languageFallbackChain->getFetchLanguageCodes();
			$this->termBuffer->prefetchTerms( $entityIds, $termTypes, $languages );
		}

		return $labelDescriptionLookup;
	}

}

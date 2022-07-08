<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheFacade;
use Wikibase\Lib\TermLanguageFallbackChain;

/**
 * Wraps another label description lookup to resolve redirects and add caching.
 * Use {@link FallbackLabelDescriptionLookupFactory} instead of using this class directly.
 *
 * @license GPL-2.0-or-later
 *
 * @note The class uses immutable cache approach: cached data never changes once persisted.
 *       For this purpose we not only include Item ID in cache key construction, but also
 *       Item's current revision ID. Revisions never change, the cached data does not need
 *       to change either, which means that we don't need to purge caches. As soon as new revision
 *       is created, cache key will change and old cache data will eventually be purged by
 *       the caching system (eg. APC, Memcached, ...) based on a Least Recently Used strategy
 *       as soon as no code will request it anymore.
 */
class CachingFallbackLabelDescriptionLookup implements FallbackLabelDescriptionLookup {

	use TermCacheKeyBuilder;

	private const LABEL = 'label';
	private const DESCRIPTION = 'description';

	/**
	 * @var TermFallbackCacheFacade
	 */
	private $cache;

	/**
	 * @var RedirectResolvingLatestRevisionLookup
	 */
	private $redirectResolvingRevisionLookup;

	/**
	 * @var FallbackLabelDescriptionLookup
	 */
	private $labelDescriptionLookup;

	/**
	 * @var TermLanguageFallbackChain
	 */
	private $termLanguageFallbackChain;

	/**
	 * @param TermFallbackCacheFacade $fallbackCache
	 * @param RedirectResolvingLatestRevisionLookup $redirectResolvingRevisionLookup
	 * @param FallbackLabelDescriptionLookup $labelDescriptionLookup
	 * @param TermLanguageFallbackChain $termLanguageFallbackChain
	 */
	public function __construct(
		TermFallbackCacheFacade $fallbackCache,
		RedirectResolvingLatestRevisionLookup $redirectResolvingRevisionLookup,
		FallbackLabelDescriptionLookup $labelDescriptionLookup,
		TermLanguageFallbackChain $termLanguageFallbackChain
	) {
		$this->cache = $fallbackCache;
		$this->redirectResolvingRevisionLookup = $redirectResolvingRevisionLookup;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
		$this->termLanguageFallbackChain = $termLanguageFallbackChain;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws LabelDescriptionLookupException
	 * @return TermFallback|null
	 */
	public function getDescription( EntityId $entityId ) {
		$languageCodes = $this->termLanguageFallbackChain->getFetchLanguageCodes();
		if ( !$languageCodes ) {
			// Can happen when the current interface language is not a valid term language, e.g. "de-formal"
			return null;
		}

		$languageCode = $languageCodes[0];
		$description = $this->getTerm( $entityId, $languageCode, self::DESCRIPTION );

		return $description;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws LabelDescriptionLookupException
	 * @return TermFallback|null
	 */
	public function getLabel( EntityId $entityId ) {
		$languageCodes = $this->termLanguageFallbackChain->getFetchLanguageCodes();
		if ( !$languageCodes ) {
			// Can happen when the current interface language is not a valid term language, e.g. "de-formal"
			return null;
		}

		$languageCode = $languageCodes[0];
		$label = $this->getTerm( $entityId, $languageCode, self::LABEL );

		return $label;
	}

	private function getTerm( EntityId $entityId, $languageCode, $termName = self::LABEL ) {
		$resolutionResult = $this->redirectResolvingRevisionLookup->lookupLatestRevisionResolvingRedirect( $entityId );
		if ( $resolutionResult === null ) {
			return null;
		}

		list( $revisionId, $targetEntityId ) = $resolutionResult;

		$termFallback = $this->cache->get( $targetEntityId, $revisionId, $languageCode, $termName );
		if ( $termFallback === TermFallbackCacheFacade::NO_VALUE ) {
			$termFallback = $termName === self::LABEL
				? $this->labelDescriptionLookup->getLabel( $targetEntityId )
				: $this->labelDescriptionLookup->getDescription( $targetEntityId );

			$this->cache->set( $termFallback, $targetEntityId, $revisionId, $languageCode, $termName );

			return $termFallback;
		}

		return $termFallback;
	}
}

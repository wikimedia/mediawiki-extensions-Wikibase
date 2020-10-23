<?php

namespace Wikibase\Lib\Store;

use Psr\SimpleCache\CacheInterface;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\TermLanguageFallbackChain;

/**
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

	const FIELD_LANGUAGE = 'language';
	const FIELD_VALUE = 'value';
	const FIELD_REQUEST_LANGUAGE = 'requestLanguage';
	const FIELD_SOURCE_LANGUAGE = 'sourceLanguage';

	const NO_VALUE = 'no value in cache';

	/**
	 * @var CacheInterface
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
	 * @var int
	 */
	private $cacheTtlInSeconds;

	/**
	 * @param CacheInterface $cache
	 * @param RedirectResolvingLatestRevisionLookup $redirectResolvingRevisionLookup
	 * @param FallbackLabelDescriptionLookup $labelDescriptionLookup
	 * @param TermLanguageFallbackChain $termLanguageFallbackChain
	 * @param int $cacheTtlInSeconds
	 */
	public function __construct(
		CacheInterface $cache,
		RedirectResolvingLatestRevisionLookup $redirectResolvingRevisionLookup,
		FallbackLabelDescriptionLookup $labelDescriptionLookup,
		TermLanguageFallbackChain $termLanguageFallbackChain,
		$cacheTtlInSeconds
	) {
		$this->cache = $cache;
		$this->redirectResolvingRevisionLookup = $redirectResolvingRevisionLookup;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
		$this->termLanguageFallbackChain = $termLanguageFallbackChain;
		$this->cacheTtlInSeconds = $cacheTtlInSeconds;
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

		$cacheKey = $this->buildCacheKey( $targetEntityId, $revisionId, $languageCode, $termName );
		$result = $this->cache->get( $cacheKey, self::NO_VALUE );
		if ( $result === self::NO_VALUE ) {
			$term = $termName === self::LABEL
				? $this->labelDescriptionLookup->getLabel( $targetEntityId )
				: $this->labelDescriptionLookup->getDescription( $targetEntityId );

			$serialization = $this->serialize( $term );

			$this->cache->set( $cacheKey, $serialization, $this->cacheTtlInSeconds );

			return $term;
		}

		if ( $result === null ) {
			return $result;
		}

		$term = $this->unserialize( $result );

		return $term;
	}

	/**
	 * @param TermFallback|null $termFallback
	 * @return array|null
	 */
	private function serialize( TermFallback $termFallback = null ) {
		if ( $termFallback === null ) {
			return null;
		}

		return [
			self::FIELD_LANGUAGE => $termFallback->getActualLanguageCode(),
			self::FIELD_VALUE => $termFallback->getText(),
			self::FIELD_REQUEST_LANGUAGE => $termFallback->getLanguageCode(),
			self::FIELD_SOURCE_LANGUAGE => $termFallback->getSourceLanguageCode(),
		];
	}

	/**
	 * @param array|null $serialized
	 * @return null|TermFallback
	 */
	private function unserialize( $serialized ) {
		if ( $serialized === null ) {
			return null;
		}

		$termData = $serialized;
		return new TermFallback(
			$termData[self::FIELD_REQUEST_LANGUAGE],
			$termData[self::FIELD_VALUE],
			$termData[self::FIELD_LANGUAGE],
			$termData[self::FIELD_SOURCE_LANGUAGE]
		);
	}

}

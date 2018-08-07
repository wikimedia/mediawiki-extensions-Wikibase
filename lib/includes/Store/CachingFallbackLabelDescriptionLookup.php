<?php

namespace  Wikibase\Lib\Store;

use Psr\SimpleCache\CacheInterface;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\LanguageFallbackChain;

/**
 * @license GPL-2.0-or-later
 *
 * @note The class uses immutable cache approach: cached data never changes once persisted.
 *       For this purpose we not only include Item ID in cache key construction, but also
 *       Item's current revision ID. Revisions never change, the cached data doesn not need
 *       to change as well, what means that we don't need to purge caches. As soon as new revision
 *       is created, cache key will change and old cache data will eventually be purged by
 *       the caching system (eg. APC, Memcached, ...) as Least Recently Used  as soon as no code
 *       will request it.
 */
class CachingFallbackLabelDescriptionLookup implements LabelDescriptionLookup {

	// TODO: make private constant when possible (PHP 7.1+)
	/* private */ const LABEL = 'label';
	/* private */ const DESCRIPTION = 'description';

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
	 * @var EntityRevisionLookup
	 */
	private $revisionLookup;

	/**
	 * @var LabelDescriptionLookup
	 */
	private $labelDescriptionLookup;

	/**
	 * @var LanguageFallbackChain
	 */
	private $languageFallbackChain;

	/**
	 * @var int
	 */
	private $cacheTtlInSeconds;

	/**
	 * @param CacheInterface $cache
	 * @param EntityRevisionLookup $revisionLookup
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 * @param LanguageFallbackChain $languageFallbackChain
	 * @param int $cacheTtlInSeconds
	 */
	public function __construct(
		CacheInterface $cache,
		EntityRevisionLookup $revisionLookup,
		LabelDescriptionLookup $labelDescriptionLookup,
		LanguageFallbackChain $languageFallbackChain,
		$cacheTtlInSeconds
	) {
		$this->cache = $cache;
		$this->revisionLookup = $revisionLookup;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
		$this->languageFallbackChain = $languageFallbackChain;
		$this->cacheTtlInSeconds = $cacheTtlInSeconds;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws LabelDescriptionLookupException
	 * @return TermFallback|null
	 */
	public function getDescription( EntityId $entityId ) {
		$languageCodes = $this->languageFallbackChain->getFetchLanguageCodes();

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
		$languageCodes = $this->languageFallbackChain->getFetchLanguageCodes();
		$languageCode = $languageCodes[0];
		$label = $this->getTerm( $entityId, $languageCode, self::LABEL );

		return $label;
	}

	private function getTerm( EntityId $entityId, $languageCode, $termName = self::LABEL ) {
		$cacheKey = $this->getCacheKey( $entityId, $languageCode, $termName );
		$result = $this->cache->get( $cacheKey, self::NO_VALUE );
		if ( $result === self::NO_VALUE ) {
			$term = $termName === self::LABEL
				? $this->labelDescriptionLookup->getLabel( $entityId )
				: $this->labelDescriptionLookup->getDescription( $entityId );

			$this->cacheTerm( $entityId, $languageCode, $termName, $term );

			return $term;
		}

		if ( $result === null ) {
			return $result;
		}

		$term = $this->unserialize( $result );

		return $term;
	}

	private function cacheTerm( EntityId $entityId, $languageCode, $termName, TermFallback $term = null ) {
		$cacheKey = $this->getCacheKey( $entityId, $languageCode, $termName );

		$serialization = $this->serialize( $term );

		$this->cache->set( $cacheKey, $serialization, $this->cacheTtlInSeconds );
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

	/**
	 * @param EntityId $entityId
	 * @param string $languageCode
	 * @param string $termName
	 * @return string
	 */
	private function getCacheKey( EntityId $entityId, $languageCode, $termName ) {
		$revisionId = $this->revisionLookup->getLatestRevisionId( $entityId );

		return "{$entityId->getSerialization()}_{$revisionId}_{$languageCode}_{$termName}";
	}

}

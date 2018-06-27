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
 */
class CachingFallbackLabelDescriptionLookup implements LabelDescriptionLookup {

	// TODO: make private constant when possible (PHP 7.1+)
	const LABEL = 'label';
	const DESCRIPTION = 'description';

	const FIELD_LANGUAGE = 'language';
	const FIELD_VALUE = 'value';
	const FIELD_REQUEST_LANGUAGE = 'requestLanguage';
	const FIELD_SOURCE_LANGUAGE = 'sourceLanguage';

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
	 * @var int Cache TTL in seconds
	 */
	private $ttl = 3600;

	/**
	 * @param CacheInterface $cache
	 * @param EntityRevisionLookup $revisionLookup
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 * @param LanguageFallbackChain $languageFallbackChain
	 * @param int $ttl
	 */
	public function __construct(
		CacheInterface $cache,
		EntityRevisionLookup $revisionLookup,
		LabelDescriptionLookup $labelDescriptionLookup,
		LanguageFallbackChain $languageFallbackChain,
		$ttl
	) {
		$this->cache = $cache;
		$this->revisionLookup = $revisionLookup;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
		$this->languageFallbackChain = $languageFallbackChain;
		$this->ttl = $ttl;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws LabelDescriptionLookupException
	 * @return TermFallback|null
	 */
	public function getDescription( EntityId $entityId ) {
		$languageCodes = $this->languageFallbackChain->getFetchLanguageCodes();
		return $this->getAndCache( $entityId, $languageCodes[0], self::DESCRIPTION );
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws LabelDescriptionLookupException
	 * @return TermFallback|null
	 */
	public function getLabel( EntityId $entityId ) {
		$languageCodes = $this->languageFallbackChain->getFetchLanguageCodes();
		return $this->getAndCache( $entityId, $languageCodes[0], self::LABEL );
	}

	// TODO: this method is not really following SRP. Do we want to split it to one
	// getting a term fallback object or null (from cache or from the lookup), and
	// the other storing the non-null result in cache?
	private function getAndCache( EntityId $entityId, $languageCode, $termName = self::LABEL ) {
		$revisionId = $this->revisionLookup->getLatestRevisionId( $entityId );

		//FIXME prefix?
		$cacheKey = "{$entityId->getSerialization()}_{$revisionId}_{$languageCode}_{$termName}";
		$result = $this->cache->get( $cacheKey );
		if ( !$result ) {
			$term = $termName === self::LABEL
				? $this->labelDescriptionLookup->getLabel( $entityId )
				: $this->labelDescriptionLookup->getDescription( $entityId );

			// If there is no label, even language fallback chain was applied, this is really
			// an edge case that should probably never happen? Do not do special handling for cache, just ignore.
			if ( $term ) {
				$serialization = $this->serialize( $term );
				$this->cache->set( $cacheKey, $serialization,  $this->ttl );
			}

			return $term;
		}

		$term = $this->unserialize( $result );

		return $term;
	}

	/**
	 * @param TermFallback $termFallback
	 * @return string
	 */
	private function serialize( TermFallback $termFallback ) {
		return json_encode( [
			self::FIELD_LANGUAGE => $termFallback->getActualLanguageCode(),
			self::FIELD_VALUE => $termFallback->getText(),
			self::FIELD_REQUEST_LANGUAGE => $termFallback->getLanguageCode(),
			self::FIELD_SOURCE_LANGUAGE => $termFallback->getSourceLanguageCode(),
		] );
	}

	private function unserialize( $serialized ) {
		$termData = json_decode( $serialized, true );
		return new TermFallback(
			$termData[self::FIELD_REQUEST_LANGUAGE],
			$termData[self::FIELD_VALUE],
			$termData[self::FIELD_LANGUAGE],
			$termData[self::FIELD_SOURCE_LANGUAGE]
		);
	}

}

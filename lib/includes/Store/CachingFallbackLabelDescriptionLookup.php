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
		// TODO: Implement getDescription() method.
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws LabelDescriptionLookupException
	 * @return TermFallback|null
	 */
	public function getLabel( EntityId $entityId ) {
		$languageCodes = $this->languageFallbackChain->getFetchLanguageCodes();
		return $this->getAndCache( $entityId, $languageCodes[0], 'label' );
	}

	private function getAndCache( EntityId $entityId, $languageCode, $termName = 'label' ) {
		$revisionId = $this->revisionLookup->getLatestRevisionId( $entityId );

		//FIXME prefix?
		$cacheKey = "{$entityId->getSerialization()}_{$revisionId}_{$languageCode}_{$termName}";
		$result = $this->cache->get( $cacheKey );
		if ( !$result ) {
			$term = $this->labelDescriptionLookup->getLabel( $entityId );

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
	 * @param TermFallback $termFallbackOrNull
	 * @return string
	 */
	private function serialize( TermFallback $termFallbackOrNull ) {
		return json_encode( [
			'language' => $termFallbackOrNull->getActualLanguageCode(),
			'value' => $termFallbackOrNull->getText(),
			'requestLanguage' => $termFallbackOrNull->getLanguageCode(),
			'sourceLanguage' => $termFallbackOrNull->getSourceLanguageCode(),
		] );
	}

	private function unserialize( $serialized ) {
		$termData = json_decode( $serialized, true );
		return new TermFallback( $termData['requestLanguage'], $termData['value'], $termData['language'], $termData['sourceLanguage'] );
	}

}

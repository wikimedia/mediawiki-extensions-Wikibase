<?php

namespace  Wikibase\Lib\Store;

use Psr\SimpleCache\CacheInterface;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\DataModel\Term\TermFallback;

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
	 * @var int Cache TTL in seconds
	 */
	private $ttl = 3600;

	/**
	 * CachingFallbackLabelDescriptionLookup constructor.
	 * @param CacheInterface $cache
	 * @param EntityRevisionLookup $revisionLookup
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 * @param int $ttl
	 */
	public function __construct(
		CacheInterface $cache,
		EntityRevisionLookup $revisionLookup,
		LabelDescriptionLookup $labelDescriptionLookup,
		$ttl
	) {
		$this->cache = $cache;
		$this->revisionLookup = $revisionLookup;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
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
		// TODO: Implement getLabel() method.
	}

	private function sasd(EntityId $entityId, $languageCode, $termName = 'label') {
		$revisionId = $this->revisionLookup->getLatestRevisionId($entityId);

		//FIXME prefix?
		$cacheKey = "{$entityId->getSerialization()}_{$revisionId}_{$languageCode}_{$termName}";
		$result = $this->cache->get( $cacheKey );
		if (!$result) {
			$term = $this->labelDescriptionLookup->getLabel( $entityId );

			//FIXME Serialize
			$serialization = $this->serialize($term);
			$this->cache->set( $cacheKey, $term,  $this->ttl);

			return $term;
		}

		$term = $this->unserialize($result);

		return $term;
	}

}

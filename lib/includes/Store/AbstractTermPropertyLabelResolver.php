<?php

namespace Wikibase\Lib\Store;

use BagOStuff;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Term\PropertyLabelResolver;

/**
 * Resolves property labels (which are unique per language) into entity IDs, uses
 * in-process caching.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
abstract class AbstractTermPropertyLabelResolver implements PropertyLabelResolver {

	/**
	 * The language to use for looking up labels
	 *
	 * @var string
	 */
	protected $languageCode;

	/**
	 * @var BagOStuff
	 */
	private $cache;

	/**
	 * @var int
	 */
	private $cacheDuration;

	/**
	 * @var string
	 */
	private $cacheKey;

	/**
	 * Maps labels to property IDs.
	 *
	 * @var EntityId[]|null
	 */
	private $propertiesByLabel = null;

	/**
	 * @param string $languageCode The language of the labels to look up (typically, the wiki's content language)
	 * @param BagOStuff $cache      The cache to use for labels (typically from ObjectCache::getLocalClusterInstance())
	 * @param int $cacheDuration    Number of seconds to keep the cached version for.
	 *                              Defaults to 3600 seconds = 1 hour.
	 * @param string $cacheKey      The cache key to use, auto-generated based on $lang per default.
	 *                              Should be set to something including the wiki name
	 *                              of the wiki that maintains the properties.
	 */
	public function __construct(
		$languageCode,
		BagOStuff $cache,
		$cacheDuration,
		$cacheKey
	) {
		$this->languageCode = $languageCode;
		$this->cache = $cache;
		$this->cacheDuration = $cacheDuration;
		$this->cacheKey = $cacheKey;
	}

	/**
	 * @param string[] $labels
	 * @param string $recache Flag, set to 'recache' to fetch fresh data from the database.
	 *
	 * @return EntityId[] a map of strings from $labels to the corresponding entity ID.
	 */
	public function getPropertyIdsForLabels( array $labels, $recache = '' ) {
		$props = $this->getLabelMap( $recache );

		$keys = array_flip( $labels );
		$idsForLabels = array_intersect_key( $props, $keys );

		return $idsForLabels;
	}

	/**
	 * Returns a map of labels to EntityIds for all Properties currently defined.
	 * The information is taking from the cache if possible, and loaded from a MatchingTermsLookup if not.
	 *
	 * @param string $recache Flag, set to 'recache' to fetch fresh data from the database.
	 *
	 * @return EntityId[]
	 */
	protected function getLabelMap( $recache = '' ) {
		if ( $this->propertiesByLabel !== null ) {
			// in-process cache
			return $this->propertiesByLabel;
		}

		$cached = $this->getCachedLabelMap( $recache );

		if ( $cached !== false && $cached !== null ) {
			$this->propertiesByLabel = $cached;
			return $this->propertiesByLabel;
		}

		$this->propertiesByLabel = $this->loadProperties();

		$this->cache->set( $this->cacheKey, $this->propertiesByLabel, $this->cacheDuration );

		return $this->propertiesByLabel;
	}

	/**
	 * @return EntityId[] Map of labels to EntityIds
	 */
	abstract protected function loadProperties(): array;

	/**
	 * @param mixed $recache
	 *
	 * @return array|false
	 */
	protected function getCachedLabelMap( $recache ) {
		$cached = false;

		if ( $recache !== 'recache' ) {
			$cached = $this->cache->get( $this->cacheKey );

			if ( is_array( $cached ) && $this->needsRecache( $cached ) ) {
				$cached = false;
			}
		}

		return $cached;
	}

	/**
	 * Checks if recache is needed
	 *
	 * @param array $propertyIds
	 *
	 * @return bool
	 */
	protected function needsRecache( array $propertyIds ) {
		foreach ( $propertyIds as $propertyId ) {
			if ( !( $propertyId instanceof PropertyId ) ) {
				return true;
			}
		}

		return false;
	}

}

<?php

namespace Wikibase\Lib\Store;

use BagOStuff;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Term\PropertyLabelResolver;
use Wikibase\TermIndex;

/**
 * Resolves property labels (which are unique per language) into entity IDs
 * using a TermIndex.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class TermPropertyLabelResolver implements PropertyLabelResolver {

	/**
	 * The language to use for looking up labels
	 *
	 * @var string
	 */
	private $languageCode;

	/**
	 * @var TermIndex
	 */
	private $termIndex;

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
	 * @param TermIndex $termIndex  The TermIndex service to look up labels with
	 * @param BagOStuff $cache      The cache to use for labels (typically from wfGetMainCache())
	 * @param int $cacheDuration    Number of seconds to keep the cached version for.
	 *                              Defaults to 3600 seconds = 1 hour.
	 * @param string $cacheKey      The cache key to use, auto-generated based on $lang per default.
	 *                              Should be set to something including the wiki name
	 *                              of the wiki that maintains the properties.
	 */
	public function __construct(
		$languageCode,
		TermIndex $termIndex,
		BagOStuff $cache,
		$cacheDuration,
		$cacheKey
	) {
		$this->languageCode = $languageCode;
		$this->termIndex = $termIndex;
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
	 * The information is taking from the cache if possible, and loaded from a TermIndex if not.
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

	protected function loadProperties() {
		$termTemplate = new TermIndexSearchCriteria( [
			'termType' => 'label',
			'termLanguage' => $this->languageCode,
		] );

		$terms = $this->termIndex->getMatchingTerms(
			[ $termTemplate ],
			'label',
			Property::ENTITY_TYPE,
			[
				'caseSensitive' => true,
				'prefixSearch' => false,
				'LIMIT' => false,
			]
		);

		$propertiesByLabel = [];

		foreach ( $terms as $term ) {
			$label = $term->getText();
			$propertiesByLabel[$label] = $term->getEntityId();
		}

		return $propertiesByLabel;
	}

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

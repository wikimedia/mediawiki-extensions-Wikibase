<?php

namespace Wikibase;

use BagOStuff;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * Resolves property labels (which are unique per language) into entity IDs
 * using a TermIndex.
 *
 * @license GPL 2+
 *
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class TermPropertyLabelResolver implements PropertyLabelResolver {

	/**
	 * The language to use for looking up labels
	 *
	 * @var string
	 */
	protected $lang;

	/**
	 * @var TermIndex
	 */
	protected $termIndex;

	/**
	 * @var BagOStuff
	 */
	protected $cache;

	/**
	 * @var int
	 */
	protected $cacheDuration;

	/**
	 * @var string
	 */
	protected $cacheKey;

	/**
	 * Maps labels to property IDs.
	 *
	 * @var EntityId[]
	 */
	protected $propertiesByLabel = null;

	/**
	 * @param string $lang          The language of the labels to look up (typically, the wiki's content language)
	 * @param TermIndex $termIndex  The TermIndex service to look up labels with
	 * @param BagOStuff $cache      The cache to use for labels (typically from wfGetMainCache())
	 * @param int $cacheDuration    Number of seconds to keep the cached version for.
	 *                              Defaults to 3600 seconds = 1 hour.
	 * @param string $cacheKey      The cache key to use, auto-generated based on $lang per default.
	 *                              Should be set to something including the wiki name
	 *                              of the wiki that maintains the properties.
	 */
	public function __construct( $lang, TermIndex $termIndex, BagOStuff $cache, $cacheDuration, $cacheKey ) {
		$this->lang = $lang;
		$this->cache = $cache;
		$this->termIndex = $termIndex;
		$this->cacheDuration = $cacheDuration;
		$this->cacheKey = $cacheKey;
	}

	/**
	 * @param string[] $labels the labels
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

		wfProfileIn( __METHOD__ );

		$cached = $this->getCachedLabelMap( $recache );

		if ( $cached !== false && $cached !== null ) {
			$this->propertiesByLabel = $cached;

			wfProfileOut( __METHOD__ );
			return $this->propertiesByLabel;
		}

		$this->propertiesByLabel = $this->loadProperties();

		$this->cache->set( $this->cacheKey, $this->propertiesByLabel, $this->cacheDuration );

		wfProfileOut( __METHOD__ );

		return $this->propertiesByLabel;

	}

	protected function loadProperties() {
		wfProfileIn( __METHOD__ );

		$termTemplate = new Term( array(
			'termType' => 'label',
			'termLanguage' => $this->lang,
			'entityType' => Property::ENTITY_TYPE
		) );

		$terms = $this->termIndex->getMatchingTerms(
			array( $termTemplate ),
			'label',
			Property::ENTITY_TYPE,
			array(
				'caseSensitive' => true,
				'prefixSearch' => false,
				'LIMIT' => false,
			)
		);

		$propertiesByLabel = array();

		foreach ( $terms as $term ) {
			$label = $term->getText();
			$propertiesByLabel[$label] = $term->getEntityId();
		}

		wfProfileOut( __METHOD__ );

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
		foreach( $propertyIds as $propertyId ) {
			if ( !( $propertyId instanceof PropertyId ) ) {
				return true;
			}
		}

		return false;
	}

}

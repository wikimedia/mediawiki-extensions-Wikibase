<?php

namespace Wikibase\Lib\Store;

use Psr\SimpleCache\CacheInterface;
use Wikibase\DataModel\Entity\EntityId;

class CachingLabelDescriptionLookupForBatch implements LabelDescriptionLookupForBatch {

	/**
	 * @var EntityRevisionCache
	 */
	private $revisionCache;

	/**
	 * @var CacheInterface
	 */
	private $termCache;

	/**
	 * @var LabelDescriptionLookupForBatch
	 */
	private $fallbackLookup;

	public function __construct( EntityRevisionCache $revisionCache, CacheInterface $termCache, LabelDescriptionLookupForBatch $fallbackLookup ) {
		$this->revisionCache = $revisionCache;
		$this->termCache = $termCache;
		$this->fallbackLookup = $fallbackLookup;
	}

	public function getLabels( array $ids, array $languageCodes ) {
		$revisionIds = $this->revisionCache->getMultiple( array_map( function ( EntityId $id ) { return $id->getSerialization(); }, $ids ) );

		$labelKeys = [];
		foreach ( $revisionIds as $entityId => $revisionId ) {
			if ( $revisionId === null ) {
				// TODO: what
				continue;
			}

			foreach ( $languageCodes as $languageCode ) {
				$labelKeys[] = "{$entityId}_{$revisionId}_{$languageCode}_label";
			}
		}

		$cachedLabels = $this->termCache->getMultiple( $labelKeys );

		$labels = array_map( 'unserialize', array_filter( $cachedLabels ) );
		// TODO: $missingIds is a list of cache keys, not entity ids
		$missingIds = array_keys( array_filter( $cachedLabels, function( $x ) { return $x === null; } ) );

		$labelsFromFallback = $this->fallbackLookup->getLabels( $missingIds, $languageCodes );

		return array_keys( $labels, $labelsFromFallback );
	}

	public function getDescriptions(array $ids, array $languageCodes ) {
		// TODO: Implement getDescriptions() method.
	}

}
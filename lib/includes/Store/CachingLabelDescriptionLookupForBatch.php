<?php

namespace Wikibase\Lib\Store;

use Psr\SimpleCache\CacheInterface;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParser;

/**
 * @license GPL-2.0-or-later
 */
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

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	public function __construct(
		EntityRevisionCache $revisionCache,
		CacheInterface $termCache,
		LabelDescriptionLookupForBatch $fallbackLookup
	) {
		$this->revisionCache = $revisionCache;
		$this->termCache = $termCache;
		$this->fallbackLookup = $fallbackLookup;
		// TODO: inject
		$this->idParser = new BasicEntityIdParser();
	}

	public function getLabels( array $ids, array $languageCodes ) {
		$revisionIds = $this->revisionCache->getMultiple( $ids );

		$labelKeys = [];
		$missingIds = [];
		foreach ( $revisionIds as $entityId => $revisionId ) {
			if ( $revisionId === null ) {
				$missingIds[] = $entityId;
				continue;
			}

			foreach ( $languageCodes as $languageCode ) {
				$labelKeys[] = "{$entityId}_{$revisionId}_{$languageCode}_label";
			}
		}

		$cachedLabels = $this->termCache->getMultiple( $labelKeys );

		$labels = array_map( 'unserialize', array_filter( $cachedLabels ) );
		// TODO: $missingIds is a list of cache keys, not entity ids
		$missingIds = array_merge(
			$missingIds,
			array_keys( array_filter(
				$cachedLabels,
				function( $x ) {
					return $x === null;
				}
			) )
		);

		$missingIds = array_map(
			function( $id ) {
				return $this->idParser->parse( $id );
			},
			$missingIds
		);

		$labelsFromFallback = $this->fallbackLookup->getLabels( $missingIds, $languageCodes );

		return array_merge( $labels, $labelsFromFallback );
	}

	public function getDescriptions( array $ids, array $languageCodes ) {
		// TODO: Implement getDescriptions() method.
		return [];
	}

}

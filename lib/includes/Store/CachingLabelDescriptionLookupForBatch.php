<?php

namespace Wikibase\Lib\Store;

use Psr\SimpleCache\CacheInterface;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;

/**
 * @license GPL-2.0-or-later
 */
class CachingLabelDescriptionLookupForBatch implements LabelDescriptionLookupForBatch {

	const TERM_LABEL = 'label';
	const TERM_DESCRIPTION = 'description';

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
		return $this->fetchTerms( $ids, $languageCodes, self::TERM_LABEL );
	}

	public function getDescriptions( array $ids, array $languageCodes ) {
		return $this->fetchTerms( $ids, $languageCodes, self::TERM_DESCRIPTION );
	}

	/**
	 * TODO: Split it up wiser!
	 * @param EntityId[] $ids
	 * @param string[] $languageCodes
	 * @return array
	 */
	private function fetchTerms( array $ids, array $languageCodes, $termType ) {
		$revisionIds = $this->revisionCache->getMultiple( $ids );

		$termKeys = [];
		$missingIds = [];
		foreach ( $revisionIds as $entityId => $revisionId ) {
			if ( $revisionId === null ) {
				$missingIds[] = $entityId;
				continue;
			}

			foreach ( $languageCodes as $languageCode ) {
				$termKeys[] = "{$entityId}_{$revisionId}_{$languageCode}_{$termType}";
			}
		}

		$cachedTerms = $this->termCache->getMultiple( $termKeys );

		$terms = array_map( 'unserialize', array_filter( $cachedTerms ) );
		// TODO: $missingIds is a list of cache keys, not entity ids
		$missingIds = array_merge(
			$missingIds,
			array_keys( array_filter(
				$cachedTerms,
				function ( $x ) {
					return $x === null;
				}
			) )
		);

		$missingIds = array_map(
			function ( $id ) {
				return $this->idParser->parse( $id );
			},
			$missingIds
		);

		if ( $termType === self::TERM_LABEL ) {
			$termsFromFallback = $this->fallbackLookup->getLabels( $missingIds, $languageCodes );
		} else {
			$termsFromFallback = $this->fallbackLookup->getDescriptions( $missingIds, $languageCodes );
		}

		return array_merge( $terms, $termsFromFallback );
	}

}

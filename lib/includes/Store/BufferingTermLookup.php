<?php

namespace Wikibase\Store;

use MapCacheLRU;
use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityTermLookupBase;
use Wikibase\Lib\Store\PrefetchingTermLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\TermIndexEntry;
use Wikibase\TermIndex;

/**
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class BufferingTermLookup extends EntityTermLookupBase implements PrefetchingTermLookup {

	/**
	 * @var MapCacheLRU
	 */
	private $buffer;

	/**
	 * @var TermIndex
	 */
	private $termIndex;

	/**
	 * @param TermIndex $termIndex
	 * @param int $bufferSize
	 */
	public function __construct( TermIndex $termIndex, $bufferSize = 1000 ) {
		$this->buffer = new MapCacheLRU( $bufferSize );
		$this->termIndex = $termIndex;
	}

	/**
	 * Returns a key for use in the LRU buffer.
	 *
	 * @param EntityId $entityId
	 * @param string $termType
	 * @param string $languageCode
	 *
	 * @return string
	 */
	private function getBufferKey( EntityId $entityId, $termType, $languageCode ) {
		return $entityId->getSerialization() . '|' . $termType . '|' . $languageCode;
	}

	/**
	 * Sets they keys for the given combinations of entity, type and language to false
	 * if they are not currently in the buffer (and not in $skipKeys).
	 *
	 * @param EntityId[] $entityIds
	 * @param string[] $termTypes
	 * @param string[] $languageCodes
	 *
	 * @return string[] the buffer keys
	 */
	private function getBufferKeys( array $entityIds, array $termTypes, array $languageCodes ) {
		$keys = [];

		foreach ( $entityIds as $entityId ) {
			foreach ( $termTypes as $termType ) {
				foreach ( $languageCodes as $languageCode ) {
					$keys[] = $this->getBufferKey( $entityId, $termType, $languageCode );
				}
			}
		}

		return $keys;
	}

	/**
	 * @param EntityId $entityId
	 * @param string $termType
	 * @param string[] $languageCodes The languages to get terms for
	 *
	 * @return string[]
	 */
	protected function getTermsOfType( EntityId $entityId, $termType, array $languageCodes ) {
		$terms = $this->getBufferedTerms( $entityId, $termType, $languageCodes );

		$languageCodes = array_diff( $languageCodes, array_keys( $terms ) );
		if ( !empty( $languageCodes ) ) {
			$bufferedKeys = $this->getBufferKeys( [ $entityId ], [ $termType ], array_keys( $terms ) );

			$fetchedTerms = $this->termIndex->getTermsOfEntity( $entityId, [ $termType ], $languageCodes );
			$fetchedKeys = $this->setBufferedTermObjects( $fetchedTerms );

			$terms = array_merge( $terms, $this->convertTermsToMap( $fetchedTerms ) );
			$bufferedKeys = array_merge( $bufferedKeys, $fetchedKeys );

			$this->setUndefinedTerms( [ $entityId ], [ $termType ], $languageCodes, $bufferedKeys );
		}

		$terms = $this->stripUndefinedTerms( $terms );
		return $terms;
	}

	/**
	 * Loads a set of terms into the buffer.
	 * The source from which to fetch would typically be supplied to the buffer's constructor.
	 *
	 * @param EntityId[] $entityIds
	 * @param string[]|null $termTypes
	 * @param string[]|null $languageCodes
	 *
	 * @throws StorageException
	 */
	public function prefetchTerms( array $entityIds, array $termTypes = null, array $languageCodes = null ) {
		MediaWikiServices::getInstance()->getStatsdDataFactory()->increment(
			'wikibase.repo.wb_terms.select.BufferingTermLookup_prefetchTerms'
		);

		$closest2Power = round( log( count( $entityIds ), 2 ) );
		$low = ceil( pow( 2, $closest2Power - 0.5 ) );
		$high = floor( pow( 2, $closest2Power + 0.5 ) );
		$idCount = $low === $high ? $low : "{$low}-{$high}";

		MediaWikiServices::getInstance()->getStatsdDataFactory()->increment(
			'wikibase.repo.wb_terms.BufferingTermLookup_prefetchTerms.idCount.' . $idCount
		);

		if ( empty( $entityIds ) ) {
			return;
		}

		// Try to detect whether the entities in question have already been prefetched, in case we
		// know the needed term types and language codes.
		// If they are not/ only partially prefetched or we don't know whether our prefetched data on
		// them is complete, we just resort to fetching them (again).
		$entityIdsToFetch = [];
		if ( $termTypes !== null && $languageCodes !== null ) {
			$entityIdsToFetch = $this->getIncompletelyPrefetchedEntityIds( $entityIds, $termTypes, $languageCodes );
		} else {
			$entityIdsToFetch = $entityIds;
		}

		if ( empty( $entityIdsToFetch ) ) {
			return;
		}

		$entityIdsByType = $this->groupEntityIds( $entityIdsToFetch );
		$terms = [];

		foreach ( $entityIdsByType as $entityIdGroup ) {
			$terms = array_merge(
				$terms,
				$this->termIndex->getTermsOfEntities( $entityIdGroup, $termTypes, $languageCodes )
			);
		}
		$bufferedKeys = $this->setBufferedTermObjects( $terms );

		if ( !empty( $languageCodes ) ) {
			$this->setUndefinedTerms( $entityIdsToFetch, $termTypes, $languageCodes, $bufferedKeys );
		}
	}

	/**
	 * Get a list of EntityIds for which we don't have all the needed data prefetched for.
	 *
	 * @param EntityId[] $entityIds
	 * @param string[] $termTypes
	 * @param string[] $languageCodes
	 *
	 * @return EntityId[]
	 */
	private function getIncompletelyPrefetchedEntityIds( array $entityIds, array $termTypes, array $languageCodes ) {
		$entityIdsToFetch = [];

		foreach ( $entityIds as $entityId ) {
			if ( $this->isIncompletelyPrefetched( $entityId, $termTypes, $languageCodes ) ) {
				$entityIdsToFetch[] = $entityId;
			}
		}

		return $entityIdsToFetch;
	}

	/**
	 * Has the term type and language code combination from the given entity already been prefeteched?
	 *
	 * @param EntityId $entityId
	 * @param string[] $termTypes
	 * @param string[] $languageCodes
	 *
	 * @return bool
	 */
	private function isIncompletelyPrefetched( EntityId $entityId, array $termTypes, array $languageCodes ) {
		foreach ( $termTypes as $termType ) {
			foreach ( $languageCodes as $lang ) {
				if ( $this->getPrefetchedTerm( $entityId, $termType, $lang ) === null ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Returns a term that was previously loaded by prefetchTerms.
	 *
	 * @param EntityId $entityId
	 * @param string $termType
	 * @param string $languageCode
	 *
	 * @return string|false|null The term, or false if that term is known to not exist,
	 *         or null if the term was not yet requested via prefetchTerms().
	 */
	public function getPrefetchedTerm( EntityId $entityId, $termType, $languageCode ) {
		$key = $this->getBufferKey( $entityId, $termType, $languageCode );
		return $this->buffer->get( $key );
	}

	/**
	 * @param EntityId $entityId
	 * @param string $termType
	 * @param string[] $languageCodes The language codes to try
	 *
	 * @return string[] The terms found in the buffer, keyed by language code. Note that this
	 *         may include negative cache values, that is, some language codes may may to false.
	 *         Use stripUndefinedTerms() to remove these.
	 */
	private function getBufferedTerms( EntityId $entityId, $termType, array $languageCodes ) {
		$terms = [];

		foreach ( $languageCodes as $lang ) {
			$term = $this->getPrefetchedTerm( $entityId, $termType, $lang );

			if ( $term !== null ) {
				$terms[$lang] = $term;
			}
		}

		return $terms;
	}

	/**
	 * @param TermIndexEntry[] $terms
	 *
	 * @return string[] The buffer keys to which the terms were assigned.
	 */
	private function setBufferedTermObjects( array $terms ) {
		$keys = [];

		foreach ( $terms as $term ) {
			$id = $term->getEntityId();

			if ( $id === null ) {
				continue;
			}

			$key = $this->getBufferKey( $id, $term->getTermType(), $term->getLanguage() );
			$this->buffer->set( $key, $term->getText() );
			$keys[] = $key;
		}

		return $keys;
	}

	/**
	 * Sets they keys for the given combinations of entity, type and language to false
	 * if they are not currently in the buffer (and not in $skipKeys).
	 *
	 * @param EntityId[] $entityIds
	 * @param string[] $termTypes
	 * @param string[] $languageCodes
	 * @param string[] $skipKeys Keys known to refer to existing terms.
	 */
	private function setUndefinedTerms( array $entityIds, array $termTypes, array $languageCodes, array $skipKeys ) {
		$skipKeys = array_flip( $skipKeys );
		$keys = $this->getBufferKeys( $entityIds, $termTypes, $languageCodes );

		foreach ( $keys as $key ) {
			if ( !isset( $skipKeys[$key] ) && !$this->buffer->has( $key ) ) {
				$this->buffer->set( $key, false );
			}
		}
	}

	/**
	 * Remove all non-string entries from an array.
	 * Useful for getting rid of negative cache entries.
	 *
	 * @param string[] $terms
	 *
	 * @return string[]
	 */
	private function stripUndefinedTerms( array $terms ) {
		return array_filter( $terms, 'is_string' );
	}

	/**
	 * @param EntityId[] $entityIds
	 *
	 * @return array[]
	 */
	private function groupEntityIds( array $entityIds ) {
		$entityIdsByType = [];

		foreach ( $entityIds as $id ) {
			$type = $id->getEntityType();
			$key = $id->getSerialization();

			$entityIdsByType[$type][$key] = $id;
		}

		return $entityIdsByType;
	}

}

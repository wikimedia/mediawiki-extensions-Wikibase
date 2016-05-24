<?php

namespace Wikibase\Store;

use MapCacheLRU;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\Store\EntityTermLookupBase;
use Wikibase\Lib\Store\StorageException;
use Wikibase\TermIndexEntry;
use Wikibase\TermIndex;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class BufferingTermLookup extends EntityTermLookupBase implements TermBuffer {

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
		$keys = array();

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
			$bufferedKeys = $this->getBufferKeys( array( $entityId ), array( $termType ), array_keys( $terms ) );

			$fetchedTerms = $this->termIndex->getTermsOfEntity( $entityId, array( $termType ), $languageCodes );
			$fetchedKeys = $this->setBufferedTermObjects( $fetchedTerms );

			$terms = array_merge( $terms, $this->convertTermsToMap( $fetchedTerms ) );
			$bufferedKeys = array_merge( $bufferedKeys, $fetchedKeys );

			$this->setUndefinedTerms( array( $entityId ), array( $termType ), $languageCodes, $bufferedKeys );
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
		if ( empty( $entityIds ) ) {
			return;
		}

		// We could first check what's already in the buffer, but it's hard to determine which
		// entities are actually "fully covered" by the cached terms. Also, our current use case
		// (the ChangesListInitRows hook) would generally, trigger only one call to prefetchTerms,
		// before any call to getTermsOfType().

		$entityIdsByType = $this->groupEntityIds( $entityIds );
		$terms = array();

		foreach ( $entityIdsByType as $entityIdGroup ) {
			$terms = array_merge(
				$terms,
				$this->termIndex->getTermsOfEntities( $entityIdGroup, $termTypes, $languageCodes )
			);
		}
		$bufferedKeys = $this->setBufferedTermObjects( $terms );

		if ( !empty( $languageCodes ) ) {
			$this->setUndefinedTerms( $entityIds, $termTypes, $languageCodes, $bufferedKeys );
		}
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

		$terms = array();
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
		$keys = array();

		foreach ( $terms as $term ) {
			$id = $term->getEntityId();

			if ( $id === null ) {
				continue;
			}

			$key = $this->getBufferKey( $id, $term->getType(), $term->getLanguage() );
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
		$entityIdsByType = array();

		foreach ( $entityIds as $id ) {
			$type = $id->getEntityType();
			$key = $id->getSerialization();

			$entityIdsByType[$type][$key] = $id;
		}

		return $entityIdsByType;
	}

}

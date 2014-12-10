<?php

namespace Wikibase\Lib\Store;

use MapCacheLRU;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Term;
use Wikibase\TermIndex;
use Wikibase\Utils;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class BufferingTermLookup extends EntityTermLookup implements TermBuffer {

	/**
	 * @var MapCacheLRU
	 */
	private $buffer;

	/**
	 * @param TermIndex $termIndex
	 * @param int $bufferSize
	 */
	public function __construct( TermIndex $termIndex, $bufferSize = 1000 ) {
		parent::__construct( $termIndex );
		$this->buffer = new MapCacheLRU( $bufferSize );
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
	 * @param EntityId $entityId
	 * @param string $termType
	 * @param string[]|null $languageCodes The languages to get terms for; null means all languages.
	 *
	 * @return string[]
	 */
	protected function getTermsOfType( EntityId $entityId, $termType, array $languageCodes = null ) {
		$terms = $this->getBufferedTerms( $entityId, $termType, $languageCodes );
		$bufferedKeys = $this->getBufferKeys( array( $entityId ), array( $termType ), array_keys( $terms ) );

		if ( $languageCodes !== null ) {
			$languageCodes = array_diff( $languageCodes, array_keys( $terms ) );
		}

		if ( $languageCodes === null || !empty( $languageCodes ) ) {
			$fetchedTerms = $this->termIndex->getTermsOfEntity( $entityId, array( $termType ), $languageCodes );
			$fetchedKeys = $this->setBufferedTerms( $fetchedTerms );

			$terms = array_merge( $terms, $this->convertTermsToMap( $fetchedTerms ) );
			$bufferedKeys = array_merge( $bufferedKeys, $fetchedKeys );
		}

		if ( !empty( $languageCodes ) ) {
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

		foreach ( $termTypes as $termType ) {
			$terms = $this->termIndex->getTermsOfEntities( $entityIds, $termType, $languageCodes );
			$bufferedKeys = $this->setBufferedTerms( $terms );

			if ( !empty( $languageCodes ) ) {
				$this->setUndefinedTerms( $entityIds, array( $termType ), $languageCodes, $bufferedKeys );
			}
		}
	}

	/**
	 * Returns a term that was previously loaded by prefetchTerms.
	 *
	 * @param EntityId $entityId
	 * @param string $termType
	 * @param string $languageCode
	 *
	 * @return string|false|null The term, or false of that term is known to not exist,
	 *         or null if the term was not yet requested via prefetchTerms().
	 */
	public function getPrefetechedTerm( EntityId $entityId, $termType, $languageCode ) {
		$key = $this->getBufferKey( $entityId, $termType, $languageCode );
		return $this->buffer->get( $key );
	}

	/**
	 * @param EntityId $entityId
	 * @param string $termType
	 * @param string[]|null $languageCodes The language codes to try; null means all languages.
	 *
	 * @return string[] The terms found in the buffer, keyed by language code. Note that this
	 *         may include negative cache values, that is, some language codes may may to false.
	 *         Use stripUndefinedTerms() to remove these.
	 */
	private function getBufferedTerms( EntityId $entityId, $termType, $languageCodes = null ) {
		if ( $languageCodes === null ) {
			$languageCodes = Utils::getLanguageCodes();
		}

		$terms = array();
		foreach ( $languageCodes as $lang ) {
			$term = $this->getPrefetechedTerm( $entityId, $termType, $lang );

			if ( $term !== null ) {
				$terms[$lang] = $term;
			}
		}

		return $terms;
	}

	/**
	 * @param Term[] $terms
	 *
	 * @return string[] The buffer keys to which the terms were assigned.
	 */
	private function setBufferedTerms( array $terms ) {
		$keys = array();

		foreach ( $terms as $term ) {
			$key = $this->getBufferKey( $term->getEntityId(), $term->getType(), $term->getLanguage() );
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
	 * @param string[] $skipKeys Keys known to not be undefined.
	 */
	private function setUndefinedTerms( array $entityIds, array  $termTypes, array $languageCodes, array $skipKeys = array() ) {
		$skipKeys = array_flip( $skipKeys );
		$keys = $this->getBufferKeys( $entityIds, $termTypes, $languageCodes );

		foreach ( $keys as $key ) {
			if ( !isset( $skipKeys[$key] ) && !$this->buffer->has( $key ) ) {
				$this->buffer->set( $key, false );
			}
		}
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
	private function getBufferKeys( array $entityIds, array  $termTypes, array $languageCodes ) {
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

}

<?php

namespace Wikibase\Lib\Store;

use MapCacheLRU;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\TermIndex;

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
	public function __construct( TermIndex $termIndex, $bufferSize = 100 ) {
		parent::__construct( $termIndex );
		$this->buffer = new MapCacheLRU( $bufferSize );
	}

	private function getBufferKey( EntityId $entityId, $termType, $languageCode ) {
		return $entityId->getSerialization() . '|' . $termType . '|' . $languageCode;
	}

	protected function getTermsOfType( $entityId, $termType, $languageCodes ) {
		$terms = $this->getBufferedTerms( $entityId, $termType, $languageCodes );

		if ( $languageCodes !== null ) {
			$languageCodes = array_diff( $languageCodes, array_keys( $terms ) );
		}

		if ( $languageCodes === null || !empty( $bufferedTerms ) ) {
			$fetchedTerms = $this->termIndex->getTermsOfEntity( $entityId, array( $termType ), $languageCodes );
			$this->setBufferedTerms( $fetchedTerms );

			$terms = array_merge( $terms, $fetchedTerms );
		}

		if ( !empty( $languageCodes ) ) {
			$this->setUndefinedTerms( array( $entityId ), array( $termType ), $languageCodes );
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
			$this->setBufferedTerms( $terms );
		}

		if ( !empty( $termTypes ) && !empty( $languageCodes ) ) {
			$this->setUndefinedTerms( $entityIds, $termTypes, $languageCodes );
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
		$key = $this->getBufferKey( $entityId, 'label', $languageCode );
		return $this->buffer->get( $key );
	}

	private function getBufferedTerms( $entityId, $termType, $languageCodes ) {
	}

	private function setBufferedTerms( $terms ) {
	}

	private function setUndefinedTerms( $array, $array1, $languageCodes ) {
	}

	private function stripUndefinedTerms( $terms ) {
	}

}

<?php

namespace Wikibase\Lib\Tests\Store\Sql\MediaWikiTermStore;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\Sql\MediaWikiTermStore\SchemaAccess;

/**
 * Accessor that stores entity terms in memory, and provides helper functions
 * to isolate unit tests that needs schema access from binding to actual
 * persistent storage mechanisms
 */
class InMemoryEntityTermStoreAccess implements EntityTermStoreSchemaAccess {
	private $entityTerms;

	/**
	 * @inheritdoc
	 */
	public function setTerms( $entityType, EntityId $entityId, array $termsArray ){
		$this->entityTerms[ $entityType ][ $entityId->getNumericId() ] = $termsArray;
	}

	/**
	 * @inheritdoc
	 */
	public function unsetTerms( $entityType, EntityId $entityId ) {
		if ( isset( $this->entityTerms[ $entityType ][ $entityId->getNumericId() ] ) ) {
			unset( $this->entityTerms[ $entityType ][ $entityId->getNumericId() ] );
		}
	}

	// test helper methods

	/**
	 * check that terms for the given property id and fingerprint exist in memory as-is
	 * @return bool
	 */
	public function hasPropertyTerms( $entityType, EntityId $entityId, $termsArray ) {
		if ( !isset( $this->entityTerms[ $entityType ][ $entityId->getNumericId() ] ) ) {
			return false;
		}

		return $termsArray === $this->entityTerms[ $entityType ][ $entityId->getNumericId() ];
	}

	/**
	 * Check that given property id has no terms stored for it in memory
	 */
	public function hasNoPropertyTerms( $entityType, EntityId $entityId ) {
		return !isset( $this->entityTerms[ $entityType ][ $entityId->getNumericId() ] );
	}

}

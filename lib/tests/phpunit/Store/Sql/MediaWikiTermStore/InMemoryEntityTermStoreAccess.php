<?php

namespace Wikibase\Lib\Tests\Store\Sql\MediaWikiTermStore;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\Sql\MediaWikiTermStore\EntityTermStoreSchemaAccess;

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
	public function setTerms( EntityId $entityId, array $termsArray ){
		$entityType = $entityId->getEntityType();
		$this->entityTerms[ $entityType ][ $entityId->getNumericId() ] = $termsArray;
	}

	/**
	 * @inheritdoc
	 */
	public function unsetTerms( EntityId $entityId ) {
		$entityType = $entityId->getEntityType();
		if ( isset( $this->entityTerms[ $entityType ][ $entityId->getNumericId() ] ) ) {
			unset( $this->entityTerms[ $entityType ][ $entityId->getNumericId() ] );
		}
	}

	// test helper methods

	/**
	 * check that terms for the given property id and fingerprint exist in memory as-is
	 * @return bool
	 */
	public function hasTerms( EntityId $entityId, $termsArray ) {
		$entityType = $entityId->getEntityType();
		if ( !isset( $this->entityTerms[ $entityType ][ $entityId->getNumericId() ] ) ) {
			return false;
		}

		return $termsArray === $this->entityTerms[ $entityType ][ $entityId->getNumericId() ];
	}

	/**
	 * Check that given property id has no terms stored for it in memory
	 */
	public function hasNoTerms( EntityId $entityId ) {
		$entityType = $entityId->getEntityType();
		return !isset( $this->entityTerms[ $entityType ][ $entityId->getNumericId() ] );
	}

}

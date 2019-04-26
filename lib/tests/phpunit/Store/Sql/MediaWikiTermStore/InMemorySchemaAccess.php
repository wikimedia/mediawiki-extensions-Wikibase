<?php

namespace Wikibase\Lib\Tests\Store\Sql\MediaWikiTermStore;

use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Lib\Store\Sql\MediaWikiTermStore\SchemaAccess;

class InMemorySchemaAccess implements SchemaAccess {
	private $propertyTerms;

	/**
	 * @inheritdoc
	 */
	public function setPropertyTerms( $propertyId, array $termsArray) {
		$this->propertyTerms[ $propertyId ] = $termsArray;
	}

	/**
	 * @inheritdoc
	 */
	public function clearPropertyTerms( $propertyId ) {
		if ( isset( $this->propertyTerms[ $propertyId ] ) ) {
			unset( $this->propertyTerms[ $propertyId ] );
		}
	}

	// test helper methods

	/**
	 * check that terms for the given property id and fingerprint exist in memory as-is
	 * @return bool
	 */
	public function hasPropertyTerms( $propertyId, $termsArray ) {
		if ( !isset( $this->propertyTerms[ $propertyId ] ) ) {
			return false;
		}

		return $termsArray === $this->propertyTerms[ $propertyId ];
	}

	/**
	 * Check that given property id has no terms stored for it in memory
	 */
	public function hasNoPropertyTerms( $propertyId ) {
		return !isset( $this->propertyTerms[ $propertyId ] )
			|| empty( $this->propertyTerms[ $propertyId ] );
	}

}

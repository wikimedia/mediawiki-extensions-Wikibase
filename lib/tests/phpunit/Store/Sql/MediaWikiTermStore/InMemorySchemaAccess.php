<?php

namespace Wikibase\Lib\Tests\Store\Sql\MediaWikiTermStore;

use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Lib\Store\Sql\MediaWikiTermStore\SchemaAccess;

class InMemorySchemaAccess implements SchemaAccess {
	private $propertyTerms;

	/**
	 * set property terms for a property id to the given array in memory.
	 *
	 * array must be in one of the following shape:
	 * * 'label'|'description' => lang => text
	 * * 'alias' => lang => [ text1, text2 ... ]
	 *
	 * when null|[] is given, it will unset the propertyId in-memory, effictively
	 * removing all previous terms set on that property.
	 */
	public function setPropertyTerms( $propertyId, array $termsArray) {
		$this->propertyTerms[ $propertyId ] = $termsArray;
	}

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

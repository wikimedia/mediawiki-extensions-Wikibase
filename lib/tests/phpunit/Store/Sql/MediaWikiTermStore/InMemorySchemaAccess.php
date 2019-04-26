<?php

namespace Wikibase\Lib\Tests\Store\Sql\MediaWikiTermStore;

use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Lib\Store\Sql\MediaWikiTermStore\SchemaAccess;

class InMemorySchemaAccess implements SchemaAccess {
	private $propertyTerms;

	public function setPropertyLabel( $propertyId, $lang, $text ) {
		$this->propertyTerms[ $propertyId ]['label'][ $lang ] = $text;
	}

	public function setPropertyDescription( $propertyId, $lang, $text ) {
		$this->propertyTerms[ $propertyId ]['desc'][ $lang ] = $text;
	}

	public function setPropertyAlias( $propertyId, $lang, $text ) {
		$this->propertyTerms[ $propertyId ]['alias'][ $lang ][] = $text;
	}

	public function clearPropertyTerms( $propertyId ) {
		if ( isset( $this->propertyTerms[ $propertyId ] ) ) {
			unset( $this->propertyTerms[ $propertyId ] );
		}
	}

	// helper test methods

	public function setPropertyTermsFromFingerprint( $propertyId, Fingerprint $fingerprint ) {
		$this->setPropertyTerms( $propertyId, $this->fingerprintToArray( $fingerprint ) );
	}

	/**
	 * set property terms for a property id to the given array in memory.
	 *
	 * array must be in one of the following shape:
	 * * 'label'|'description' => lang => text
	 * * 'alias' => lang => [ text1, text2 ... ]
	 * * null|[]
	 *
	 * when null|[] is given, it will unset the propertyId in-memory, effictively
	 * removing all previous terms set on that property.
	 */
	public function setPropertyTerms( $propertyId, $termsArray = null) {
		if ( empty ( $termsArray ) && isset( $this->propertyTerms[ $propertyId ]) ) {
			unset( $this->propertyTerms[ $propertyId ] );
		} else {
			$this->propertyTerms[ $propertyId ] = $termsArray;
		}
	}

	/**
	 * check that terms for the given property id and fingerprint exist in memory as-is
	 * @return bool
	 */
	public function hasPropertyTerms( $propertyId, Fingerprint $fingerprint ) {
		if ( !isset( $this->propertyTerms[ $propertyId ] ) ) {
			return false;
		}

		$propertyTerms = $this->fingerprintToArray( $fingerprint );

		$storedPropertyTerms = $this->propertyTerms[ $propertyId ];

		return $propertyTerms === $storedPropertyTerms;
	}

	/**
	 * Check that given property id has no terms stored for it in memory
	 */
	public function hasNoPropertyTerms( $propertyId ) {
		return !isset( $this->propertyTerms[ $propertyId ] )
			|| empty( $this->propertyTerms[ $propertyId ] );
	}

	/**
	 * Convert Fingerprint instance to internal terms array represenation
	 */
	public function fingerprintToArray( Fingerprint $fingerprint ) {
		$propertyTerms = [];

		foreach ( $fingerprint->getLabels() as $label ) {
			$propertyTerms['label'][ $label->getLanguageCode() ] = $label->getText();
		}
		foreach ( $fingerprint->getDescriptions() as $desc ) {
			$propertyTerms['desc'][ $desc->getLanguageCode() ] = $desc->getText();
		}
		foreach ( $fingerprint->getAliasGroups() as $aliasGroup ) {
			$propertyTerms['alias'][ $aliasGroup->getLanguageCode() ] = $aliasGroup->getAliases();
		}

		return $propertyTerms;
	}
}

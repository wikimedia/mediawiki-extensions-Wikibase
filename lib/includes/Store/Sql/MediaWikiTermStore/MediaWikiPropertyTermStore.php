<?php

namespace Wikibase\Lib\Store\Sql\MediaWikiTermStore;

use Wikibase\TermStore\PropertyTermStore;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Fingerprint;

/**
 *
 */
class MediaWikiPropertyTermStore implements PropertyTermStore {
	private $schemaAccess;

	public function __construct( SchemaAccess $schemaAccess ) {
		// DOING: SchemaAccess design is emerging from consumer perspective (this class)
		//        as progress is made in here.
		$this->schemaAccess = $schemaAccess;
	}

	public function storeTerms( PropertyId $propertyId, Fingerprint $fingerprint ) {
		$this->schemaAccess->setPropertyTerms(
			$propertyId->getNumericId(),
			$this->fingerprintToArray( $fingerprint )
		);
	}

	public function deleteTerms( PropertyId $propertyId ) {
		$this->schemaAccess->clearPropertyTerms( $propertyId->getNumericId() );
	}

	public function getTerms( PropertyId $propertyId ): Fingerprint {
		throw new \Exception( 'not implemented' );
	}

	private function fingerprintToArray( Fingerprint $fingerprint ) {
		$propertyTerms = [];

		foreach ( $fingerprint->getLabels() as $label ) {
			$propertyTerms['label'][ $label->getLanguageCode() ] = $label->getText();
		}
		foreach ( $fingerprint->getDescriptions() as $desc ) {
			$propertyTerms['description'][ $desc->getLanguageCode() ] = $desc->getText();
		}
		foreach ( $fingerprint->getAliasGroups() as $aliasGroup ) {
			$propertyTerms['alias'][ $aliasGroup->getLanguageCode() ] = $aliasGroup->getAliases();
		}

		return $propertyTerms;
	}

}

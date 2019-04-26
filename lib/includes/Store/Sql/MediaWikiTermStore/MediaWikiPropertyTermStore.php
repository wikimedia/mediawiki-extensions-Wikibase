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
		$numericProeprtyId = $propertyId->getNumericId();

		foreach ( $fingerprint->getLabels() as $label ) {
			$this->schemaAccess->setPropertyLabel(
				$numericProeprtyId,
				$label->getLanguageCode(),
				$label->getText()
			);
		}

		foreach ( $fingerprint->getDescriptions() as $description ) {
			$this->schemaAccess->setPropertyDescription(
				$numericProeprtyId,
				$description->getLanguageCode(),
				$description->getText()
			);
		}

		foreach ( $fingerprint->getAliasGroups() as $aliasGroup ) {
			$groupLang = $aliasGroup->getLanguageCode();
			foreach ( $aliasGroup->getAliases() as $aliasText ) {
				$this->schemaAccess->setPropertyAlias(
					$numericProeprtyId,
					$groupLang,
					$aliasText
				);
			}
		}
	}

	public function deleteTerms( PropertyId $propertyId ) {
		$this->schemaAccess->clearPropertyTerms( $propertyId->getNumericId() );
	}

	public function getTerms( PropertyId $propertyId ): Fingerprint {
		throw new \Exception( 'not implemented' );
	}

}

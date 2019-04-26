<?php

namespace Wikibase\Repo\Store\Sql\MediaWikiTermStore;

use Wikibase\TermStore\PropertyTermStore;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Fingerprint;

/**
 *
 */
class MediaWikiPropertyTermStore implements PropertyTermStore {
	private $dbStore;

	public function __construct( DatabaseStore $dbStore ) {
		$this->dbStore = $dbStore;
	}

	public function storeTerms( PropertyId $propertyId, Fingerprint $terms ) {
		/* from the old attempt
		foreach ( $property->getLabels() as $label ) {
			$this->dbStore->insertPropertyLabel(
				$property->getId(),
				self::TERM_TYPE_LABEL,
				$label->getLanguageCode(),
				$label->getText()
			);
		}
		foreach ( $property->getDescriptions() as $description ) {
			$this->insertTerm(
				$property->getId(),
				self::TERM_TYPE_DESCRIPTION,
				$description->getLanguageCode(),
				$description->getText()
			);
		}
		foreach ( $property->getAliasGroups() as $aliasGroup ) {
			$groupLang = $aliasGroup->getLanguageCode();
			foreach ( $aliasGroup->getAliases() as $aliasText ) {
				$this->insertTerm(
					$property->getId(),
					self::TERM_TYPE_ALIAS,
					$groupLang,
					$aliasText
				);
			}
		}
		*/
	}

	public function deleteTerms( PropertyId $propertyId ) {
	}

	public function getTerms( PropertyId $propertyId ): Fingerprint {
	}
}

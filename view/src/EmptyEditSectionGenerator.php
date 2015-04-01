<?php

namespace Wikibase\View;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Statement\Statement;

/**
 * An EditSectionGenerator returning empty string for edit sections
 *
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
class EmptyEditSectionGenerator implements EditSectionGenerator {

	/**
	 * Get an empty string
	 *
	 * @return string
	 */
	public function getStatementEditSection( Statement $statement ) {
		return '';
	}

	/**
	 * Get an empty string
	 *
	 * @return string
	 */
	public function getLabelDescriptionAliasesEditSection( $languageCode, EntityId $entityId = null ) {
		return '';
	}

	/**
	 * Get an empty string
	 *
	 * @return string
	 */
	public function getSiteLinksEditSection( EntityId $entityId = null ) {
		return '';
	}

	/**
	 * Get an empty string
	 *
	 * @return string
	 */
	public function getAddStatementToGroupSection( PropertyId $propertyId, EntityId $entityId = null ) {
		return '';
	}

}

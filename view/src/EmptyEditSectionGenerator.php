<?php

namespace Wikibase\View;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Statement\Statement;

/**
 * An EditSectionGenerator returning empty string for edit sections
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class EmptyEditSectionGenerator implements EditSectionGenerator {

	/**
	 * @param Statement $statement
	 *
	 * @return string Always an empty string.
	 */
	public function getStatementEditSection( Statement $statement ) {
		return '';
	}

	/**
	 * @param string $languageCode Unused.
	 * @param EntityId|null $entityId Unused.
	 *
	 * @return string Always an empty string.
	 */
	public function getLabelDescriptionAliasesEditSection( $languageCode, EntityId $entityId = null ) {
		return '';
	}

	/**
	 * @param EntityId|null $entityId Unused.
	 *
	 * @return string Always an empty string.
	 */
	public function getSiteLinksEditSection( EntityId $entityId = null ) {
		return '';
	}

	/**
	 * @param PropertyId $propertyId Unused.
	 * @param EntityId|null $entityId Unused.
	 *
	 * @return string Always an empty string.
	 */
	public function getAddStatementToGroupSection( PropertyId $propertyId, EntityId $entityId = null ) {
		return '';
	}

}

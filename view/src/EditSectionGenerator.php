<?php

namespace Wikibase\View;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Statement\Statement;

/**
 * Generates HTML for a section edit link
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
interface EditSectionGenerator {

	/**
	 * Returns HTML allowing to edit site links.
	 *
	 * @param EntityId|null $entityId
	 * @return string HTML
	 */
	public function getSiteLinksEditSection( EntityId $entityId = null );

	/**
	 * Returns HTML allowing to edit label, description and aliases.
	 *
	 * @param string $languageCode
	 * @param EntityId|null $entityId
	 *
	 * @return string HTML
	 */
	public function getLabelDescriptionAliasesEditSection( $languageCode, EntityId $entityId = null );

	/**
	 * Returns HTML allowing to edit a statement
	 *
	 * @param Statement $statement
	 * @return string HTML
	 */
	public function getStatementEditSection( Statement $statement );

	/**
	 * Returns HTML allowing to add a statement to a statementgroup
	 *
	 * @param PropertyId $propertyId The property of the statement group
	 * @param EntityId|null $entityId The id of the entity on which to add a statement
	 *
	 * @return string HTML
	 */
	public function getAddStatementToGroupSection( PropertyId $propertyId, EntityId $entityId = null );

}

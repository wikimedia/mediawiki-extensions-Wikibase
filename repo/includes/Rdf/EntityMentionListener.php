<?php

namespace Wikibase\Rdf;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * Interface for tracking entities mentioned while generating RDF.
 *
 * This information can be used to generate "stub" entries for entities that
 * are referenced in the RDF output. Such stubs would typically give at
 * least a type and a label for the entity.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
interface EntityMentionListener {

	/**
	 * Should be called when an entity reference (an EntityIdValue object) is encountered.
	 *
	 * @param EntityId $id
	 */
	public function entityReferenceMentioned( EntityId $id );

	/**
	 * Should be called when a property is used in a PropertySnak.
	 *
	 * @param PropertyId $id
	 */
	public function propertyMentioned( PropertyId $id );

}

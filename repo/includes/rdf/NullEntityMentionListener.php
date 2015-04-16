<?php

namespace Wikibase\Rdf;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * Null implementation of EntityMentionListener
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class NullEntityMentionListener implements EntityMentionListener {

	/**
	 * Should be called when an entity reference (an EntityIdValue object) is encountered.
	 *
	 * @param EntityId $id
	 */
	public function entityReferenceMentioned( EntityId $id ) {}

	/**
	 * Should be called when a property is used in a PropertySnak.
	 *
	 * @param PropertyId $id
	 */
	public function propertyMentioned( PropertyId $id ) {}

}

<?php

namespace Wikibase;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * Null implementation of MentionedEntityTracker
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class NullMentionedEntityTracker implements MentionedEntityTracker {

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
	public function propertyUsed( PropertyId $id ) {}

}

<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * Service interface for getting a referenced entity (out of a specified set),
 * from a given starting entity. The starting entity, and the target entities
 * are (potentially indirectly, via intermediate entities) linked by statements
 * with a given property ID, pointing from the starting entity to one of the
 * target entities.
 *
 * @since 3.10
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
interface ReferencedEntityIdLookup {

	/**
	 * Get the referenced entity (out of $toIds), from a given entity. The starting entity, and
	 * the target entities are (potentially indirectly, via intermediate entities) linked by
	 * statements with the given property ID, pointing from the starting entity to one of the
	 * target entities.
	 * Implementations of this may or may not return the closest referenced entity (where
	 * distance is defined by the number of intermediate entities).
	 *
	 * @since 3.10
	 *
	 * @param EntityId $fromId
	 * @param PropertyId $propertyId
	 * @param EntityId[] $toIds
	 *
	 * @return EntityId|null Returns null in case none of the target entities are referenced.
	 * @throws ReferencedEntityIdLookupException
	 */
	public function getReferencedEntityId( EntityId $fromId, PropertyId $propertyId, array $toIds );

}

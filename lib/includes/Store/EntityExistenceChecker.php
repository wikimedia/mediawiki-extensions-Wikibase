<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
interface EntityExistenceChecker {

	/**
	 * This exists check returns true, iff an entity has both been created and not deleted,
	 * i.e. deleted entities do not exist.
	 *
	 * @param EntityId $id
	 *
	 * @return bool
	 */
	public function exists( EntityId $id ): bool;

	/**
	 * Checks if a batch of entities exists at once.
	 *
	 * This is equivalent to iterating over $ids and calling {@link exists()} for each,
	 * but may be more efficient, depending on implementation.
	 *
	 * @param EntityId[] $ids
	 * @return bool[] Mapping from {@link SerializableEntityId::getSerialization() entity ID serialization}
	 * to {@link exists()} result.
	 */
	public function existsBatch( array $ids ): array;

}

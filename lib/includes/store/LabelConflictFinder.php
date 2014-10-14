<?php

namespace Wikibase\Lib\Store;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Term;

/**
 * Service interface for detecting label conflicts.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
interface LabelConflictFinder {

	/**
	 * Returns a list of ids of the entities that have labels conflicting.
	 * Conflicts are defined to be inside on type of entity and language.
	 *
	 *
	 * @note: implementations must return *some* conflicts if there are *any* conflicts,
	 * but are not required to return *all* conflicts.
	 *
	 * @param string $entityType The relevant entity type
	 * @param string[] $labels The labels to look for, with language codes as keys.
	 * @param string[]|null $descriptions The descriptions to consider (if desired), wioth language codes as keys.
	 * @param EntityId|null $excludeId  Ignore conflicts with this entity ID (for ignoring self-conflicts)
	 *
	 * @return Term[]
	 */
	public function getLabelConflicts( $entityType, $labels, $descriptions = null, EntityId $excludeId = null );

}

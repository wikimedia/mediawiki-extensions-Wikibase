<?php

namespace Wikibase\Lib\Store;

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
	 * Returns a list of Terms that conflict with (that is, match) the given labels.
	 * Conflicts are defined to be inside on type of entity and language.
	 *
	 * @note: implementations must return *some* conflicts if there are *any* conflicts,
	 * but are not required to return *all* conflicts.
	 *
	 * @param string $entityType The entity type to consider for conflicts.
	 * @param string[] $labels The labels to look for, with language codes as keys.
	 *
	 * @return Term[]
	 */
	public function getLabelConflicts( $entityType, array $labels );

	/**
	 * Returns a list of Terms that conflict with (that is, match) the given labels
	 * and descriptions. Conflicts are defined to be inside on type of entity and one language.
	 * For a label to be considered a conflict, there must be a conflicting description on the
	 * same entity. From this it follows that labels with no corresponding description
	 * cannot contribute to a conflicts.
	 *
	 * @note: implementations must return *some* conflicts if there are *any* conflicts,
	 * but are not required to return *all* conflicts.
	 *
	 * @param string|null $entityType The relevant entity type
	 * @param string[] $labels The labels to look for, with language codes as keys.
	 * @param string[] $descriptions The descriptions to consider (if desired), with language codes as keys.
	 *
	 * @return Term[]
	 */
	public function getLabelWithDescriptionConflicts( $entityType, array $labels, array $descriptions );

}

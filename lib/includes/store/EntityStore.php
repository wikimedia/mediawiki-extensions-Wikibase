<?php

namespace Wikibase;
use PermissionsError;
use User;

/**
 * Storage interface for Entities.
 *
 * @see EntityLookup
 * @see EntityRevisionLookup
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
interface EntityStore extends EntityLookup, EntityRevisionLookup {

	/**
	 * Saves the given Entity to some underlying storage mechanism.
	 *
	 * @param Entity $entity the entity to save.
	 * @param string $summary the edit summary for the new revision.
	 * @param User $user the user to whom to attribute the edit
	 * @param int $flags EDIT_XXX flags, as defined for WikiPage::doEditContent.
	 * @param int|bool $baseRevId the revision ID $entity is based on. Saving should fail if
	 * $baseRevId is no longer the current revision.
	 *
	 * @see WikiPage::doEditContent
	 *
	 * @return EntityRevision
	 *
	 * @throws StorageException
	 * @throws PermissionsError
	 */
	public function saveEntity( Entity $entity, $summary, User $user, $flags = 0, $baseRevId = false );

}

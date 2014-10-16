<?php

namespace Wikibase\Lib\Store;

use PermissionsError;
use User;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityRevision;

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
interface EntityStore {

	/**
	 * Assigns a fresh ID to the given entity.
	 *
	 * @note The new ID is "consumed" after this method returns, and will not be
	 * assigned to another other entity. The next available ID for each kind of
	 * entity is considered part of the persistent state of the Wikibase
	 * installation.
	 *
	 * @note calling this method on an Entity that already has an ID, and specifically
	 * calling this method twice on the same entity, shall result in an exception.
	 *
	 * @param Entity $entity
	 *
	 * @throws StorageException
	 */
	public function assignFreshId( Entity $entity );

	/**
	 * Saves the given Entity to some underlying storage mechanism.
	 *
	 * @note: If the item does not have an ID yet (i.e. it was not yet created in the database),
	 *        saveEntity() will fail with a edit-gone-missing message unless the EDIT_NEW bit is
	 *        set in $flags. If EDIT_NEW is set and the Entity does not yet have an ID, a new ID
	 *        is assigned using assignFreshId().
	 *
	 * @note: if the save is triggered by any kind of user interaction, consider using
	 *        EditEntity::attemptSave(), which automatically handles edit conflicts, permission
	 *        checks, etc.
	 *
	 * @param Entity $entity the entity to save.
	 * @param string $summary the edit summary for the new revision.
	 * @param User $user the user to whom to attribute the edit
	 * @param int $flags EDIT_XXX flags, as defined for WikiPage::doEditContent.
	 *        Additionally, the EntityContent::EDIT_XXX constants can be used.
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

	/**
	 * Saves the given EntityRedirect to some underlying storage mechanism.
	 *
	 * @since 0.5
	 *
	 * @param EntityRedirect $redirect the redirect to save.
	 * @param string $summary the edit summary for the new revision.
	 * @param User $user the user to whom to attribute the edit
	 * @param int $flags EDIT_XXX flags, as defined for WikiPage::doEditContent.
	 * @param int|bool $baseRevId the revision ID $entity is based on. Saving should fail if
	 * $baseRevId is no longer the current revision.
	 *
	 * @see WikiPage::doEditContent
	 *
	 * @return int The new revision ID
	 *
	 * @throws StorageException
	 * @throws PermissionsError
	 */
	public function saveRedirect( EntityRedirect $redirect, $summary, User $user, $flags = 0, $baseRevId = false );

	/**
	 * Deletes the given entity in some underlying storage mechanism.
	 *
	 * @param EntityId $entityId
	 * @param string $reason the reason for deletion
	 * @param User $user
	 */
	public function deleteEntity( EntityId $entityId, $reason, User $user );

	/**
	 * Check if no edits were made by other users since the given revision.
	 * This makes the assumption that revision ids are monotonically increasing.
	 *
	 * @see EditPage::userWasLastToEdit()
	 *
	 * @param User $user the user
	 * @param EntityId $id the entity to check
	 * @param int $lastRevId the revision to check from
	 *
	 * @return bool
	 */
	public function userWasLastToEdit( User $user, EntityId $id, $lastRevId );

	/**
	 * Watches or unwatches the entity.
	 *
	 * @todo: move this to a separate service
	 *
	 * @param User $user
	 * @param EntityId $id the entity to watch
	 * @param bool $watch whether to watch or unwatch the page.
	 *
	 * @throws \MWException
	 * @return void
	 */
	public function updateWatchlist( User $user, EntityId $id, $watch );

	/**
	 * Determines whether the given user is watching the given item
	 *
	 * @todo: move this to a separate service
	 *
	 * @param User $user
	 * @param EntityId $id the entity to watch
	 *
	 * @return bool
	 */
	public function isWatching( User $user, EntityId $id );
}

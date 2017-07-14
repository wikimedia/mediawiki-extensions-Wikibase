<?php

namespace Wikibase\Repo\Store;

use InvalidArgumentException;
use Status;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Service interface for checking a user's permissions on a given entity.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
interface EntityPermissionChecker {

	const ACTION_READ = 'read';

	const ACTION_EDIT = 'edit';

	// TODO: should this be a separate action? No class asks explicitly for "create-only" permissions. And
	// There is no way to just create an entity, some of its properties must set, so it is at least some
	// kind of an "edit" action too. Maybe implementation should take care of specifics of "create" case
	// and this would be irrelevant here?
	const ACTION_CREATE = 'create';

	const ACTION_EDIT_TERMS = 'term';

	const ACTION_MERGE = 'merge';

	const ACTION_REDIRECT = 'redirect';

	/**
	 * Check whether the given user has the permission to perform the given action on an entity.
	 * This will perform a check based on the entity's ID if the entity has an ID set
	 * (that is, the entity "exists"), or based merely on the entity type, in case
	 * the entity does not exist.
	 *
	 * @param User $user
	 * @param string $action
	 * @param EntityDocument $entity
	 * @param string $quick Flag for allowing quick permission checking. If set to
	 * 'quick', implementations may return inaccurate results if determining the accurate result
	 * would be slow (e.g. checking for cascading protection).
	 * This is intended as an optimization for non-critical checks,
	 * e.g. for showing or hiding UI elements.
	 *
	 * @throws InvalidArgumentException if unknown permission is requested
	 *
	 * @return Status a status object representing the check's result.
	 */
	public function getPermissionStatusForEntity( User $user, $action, EntityDocument $entity, $quick = '' );

	/**
	 * Check whether the given user has the permission to perform the given action on an entity.
	 * This requires the ID of an existing entity.
	 *
	 * @param User $user
	 * @param string $action
	 * @param EntityId $entityId
	 * @param string $quick Flag for allowing quick permission checking. If set to
	 * 'quick', implementations may return inaccurate results if determining the accurate result
	 * would be slow (e.g. checking for cascading protection).
	 * This is intended as an optimization for non-critical checks,
	 * e.g. for showing or hiding UI elements.
	 *
	 * @throws InvalidArgumentException if unknown permission is requested
	 *
	 * @return Status a status object representing the check's result.
	 */
	public function getPermissionStatusForEntityId( User $user, $action, EntityId $entityId, $quick = '' );

	/**
	 * Check whether the given user has the permission to perform the given action on a given entity type.
	 * This does not require an entity to exist.
	 *
	 * Useful especially for checking whether the user is allowed to create an entity
	 * of a given type.
	 *
	 * @param User $user
	 * @param string $action
	 * @param string $type
	 * @param string $quick Flag for allowing quick permission checking. If set to
	 * 'quick', implementations may return inaccurate results if determining the accurate result
	 * would be slow (e.g. checking for cascading protection).
	 * This is intended as an optimization for non-critical checks,
	 * e.g. for showing or hiding UI elements.
	 *
	 * @throws InvalidArgumentException if unknown permission is requested
	 *
	 * @return Status a status object representing the check's result.
	 */
	public function getPermissionStatusForEntityType( User $user, $action, $type, $quick = '' );

}

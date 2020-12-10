<?php

namespace Wikibase\Repo\Store;

use InvalidArgumentException;
use MediaWiki\Permissions\PermissionManager;
use Status;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Service interface for checking a user's permissions on a given entity.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
interface EntityPermissionChecker {

	public const ACTION_READ = 'read';

	public const ACTION_EDIT = 'edit';

	public const ACTION_EDIT_TERMS = 'term';

	public const ACTION_MERGE = 'merge';

	public const ACTION_REDIRECT = 'redirect';

	/**
	 * Check whether the given user has the permission to perform the given action on an entity.
	 * This will perform a check based on the entity's ID if the entity has an ID set
	 * (that is, the entity "exists"), or based merely on the entity type, in case
	 * the entity does not exist.
	 *
	 * @param User $user
	 * @param string $action
	 * @param EntityDocument $entity
	 * @param string $rigor Flag for allowing quick permission checking.
	 * One of the PermissionManager::RIGOR_* constants.
	 * If set to 'PermissionManager::RIGOR_QUICK', implementations may return
	 * inaccurate results if determining the accurate result
	 * would be slow (e.g. checking for cascading protection).
	 * This is intended as an optimization for non-critical checks,
	 * e.g. for showing or hiding UI elements.
	 *
	 * @throws InvalidArgumentException if unknown permission is requested
	 *
	 * @return Status a status object representing the check's result.
	 */
	public function getPermissionStatusForEntity(
		User $user,
		$action,
		EntityDocument $entity,
		$rigor = PermissionManager::RIGOR_SECURE );

	/**
	 * Check whether the given user has the permission to perform the given action on an entity.
	 * This requires the ID of an existing entity.
	 *
	 * @param User $user
	 * @param string $action
	 * @param EntityId $entityId
	 * @param string $rigor Flag for allowing quick permission checking.
	 * One of the PermissionManager::RIGOR_* constants.
	 * If set to 'PermissionManager::RIGOR_QUICK', implementations may return
	 * inaccurate results if determining the accurate result
	 * would be slow (e.g. checking for cascading protection).
	 * This is intended as an optimization for non-critical checks,
	 * e.g. for showing or hiding UI elements.
	 *
	 * @throws InvalidArgumentException if unknown permission is requested
	 *
	 * @return Status a status object representing the check's result.
	 */
	public function getPermissionStatusForEntityId(
		User $user,
		$action,
		EntityId $entityId,
		$rigor = PermissionManager::RIGOR_SECURE );

}

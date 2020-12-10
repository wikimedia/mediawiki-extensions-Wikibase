<?php

namespace Wikibase\Repo\Store;

use InvalidArgumentException;
use MediaWiki\Permissions\PermissionManager;
use Status;
use Title;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * Checks permissions to perform actions on the entity based on MediaWiki page permissions.
 *
 * For more information on the relationship between entities and wiki pages, see
 * docs/entity-storage.wiki.
 *
 * @license GPL-2.0-or-later
 */
class WikiPageEntityStorePermissionChecker implements EntityPermissionChecker {

	private const ACTION_MW_CREATE = 'create';

	/**
	 * @var EntityNamespaceLookup
	 */
	private $namespaceLookup;

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var PermissionManager
	 */
	private $permissionManager;

	/**
	 * @var string[]
	 */
	private $availableRights;

	/**
	 * @param EntityNamespaceLookup $namespaceLookup
	 * @param EntityTitleLookup $titleLookup
	 * @param PermissionManager $permissionManager
	 * @param string[] $availableRights
	 */
	public function __construct(
		EntityNamespaceLookup $namespaceLookup,
		EntityTitleLookup $titleLookup,
		PermissionManager $permissionManager,
		array $availableRights
	) {
		$this->namespaceLookup = $namespaceLookup;
		$this->titleLookup = $titleLookup;
		$this->permissionManager = $permissionManager;
		$this->availableRights = $availableRights;
	}

	/**
	 * @see EntityPermissionChecker::getPermissionStatusForEntity
	 *
	 * @param User $user
	 * @param string $action
	 * @param EntityDocument $entity
	 * @param string $rigor
	 *
	 * @throws InvalidArgumentException if unknown permission is requested
	 *
	 * @return Status
	 */
	public function getPermissionStatusForEntity(
		User $user,
		$action,
		EntityDocument $entity,
		$rigor = PermissionManager::RIGOR_SECURE
	) {
		$id = $entity->getId();

		if ( $id === null ) {
			return $this->getPermissionStatusForEntityType(
				$user,
				[ $action, self::ACTION_MW_CREATE ],
				$entity->getType(),
				$rigor
			);
		}

		return $this->getPermissionStatusForEntityId( $user, $action, $id, $rigor );
	}

	/**
	 * @see EntityPermissionChecker::getPermissionStatusForEntityId
	 *
	 * @param User $user
	 * @param string $action
	 * @param EntityId $entityId
	 * @param string $rigor
	 *
	 * @throws InvalidArgumentException if unknown permission is requested
	 *
	 * @return Status
	 */
	public function getPermissionStatusForEntityId(
		User $user,
		$action,
		EntityId $entityId,
		$rigor = PermissionManager::RIGOR_SECURE
	) {
		$title = $this->titleLookup->getTitleForId( $entityId );

		if ( $title === null || !$title->exists() ) {
			return $this->getPermissionStatusForEntityType(
				$user,
				[ $action, self::ACTION_MW_CREATE ],
				$entityId->getEntityType(),
				$rigor
			);

		}

		return $this->checkPermissionsForActions( $user, [ $action ], $title, $entityId->getEntityType(), $rigor );
	}

	/**
	 * Check whether the given user has the permission to perform the given action on a given entity type.
	 * This does not require an entity to exist.
	 *
	 * Useful especially for checking whether the user is allowed to create an entity
	 * of a given type.
	 *
	 * @param User $user
	 * @param string[] $actions
	 * @param string $type
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
	private function getPermissionStatusForEntityType(
		User $user,
		array $actions,
		$type,
		$rigor = PermissionManager::RIGOR_SECURE
	) {
		$title = $this->getPageTitleInEntityNamespace( $type );

		return $this->checkPermissionsForActions( $user, $actions, $title, $type, $rigor );
	}

	/**
	 * @param string $entityType
	 *
	 * @return Title
	 */
	private function getPageTitleInEntityNamespace( $entityType ) {
		$namespace = $this->namespaceLookup->getEntityNamespace( $entityType ); // TODO: can be null!

		return Title::makeTitle( $namespace, '/' );
	}

	private function checkPermissionsForActions(
		User $user,
		array $actions,
		Title $title,
		$entityType,
		$rigor = PermissionManager::RIGOR_SECURE
	) {
		$status = Status::newGood();

		$mediaWikiPermissions = [];

		foreach ( $actions as $action ) {
			$mediaWikiPermissions = array_merge(
				$mediaWikiPermissions,
				$this->getMediaWikiPermissionsToCheck( $action, $entityType )
			);
		}

		$mediaWikiPermissions = array_unique( $mediaWikiPermissions );

		foreach ( $mediaWikiPermissions as $mwPermission ) {
			$partialStatus = $this->getPermissionStatus( $user, $mwPermission, $title, $rigor );
			$status->merge( $partialStatus );
		}

		return $status;
	}

	private function getMediaWikiPermissionsToCheck( $action, $entityType ) {
		if ( $action === self::ACTION_MW_CREATE ) {
			$entityTypeSpecificCreatePermission = $entityType . '-create';

			$permissions = [ 'read', 'edit', 'createpage' ];

			if ( $this->mediawikiPermissionExists( $entityTypeSpecificCreatePermission ) ) {
				$permissions[] = $entityTypeSpecificCreatePermission;
			}

			return $permissions;
		}

		if ( $action === EntityPermissionChecker::ACTION_EDIT_TERMS ) {
			$entityTypeSpecificEditTermsPermission = $entityType . '-term';

			$permissions = [ 'read', 'edit' ];
			if ( $this->mediawikiPermissionExists( $entityTypeSpecificEditTermsPermission ) ) {
				$permissions[] = $entityTypeSpecificEditTermsPermission;
			}
			return $permissions;
		}

		if ( $action === EntityPermissionChecker::ACTION_MERGE ) {
			$entityTypeSpecificMergePermission = $entityType . '-merge';

			$permissions = [ 'read', 'edit' ];
			if ( $this->mediawikiPermissionExists( $entityTypeSpecificMergePermission ) ) {
				$permissions[] = $entityTypeSpecificMergePermission;
			}
			return $permissions;
		}

		if ( $action === EntityPermissionChecker::ACTION_REDIRECT ) {
			$entityTypeSpecificRedirectPermission = $entityType . '-redirect';

			$permissions = [ 'read', 'edit' ];
			if ( $this->mediawikiPermissionExists( $entityTypeSpecificRedirectPermission ) ) {
				$permissions[] = $entityTypeSpecificRedirectPermission;
			}
			return $permissions;
		}

		if ( $action === EntityPermissionChecker::ACTION_EDIT ) {
			return [ 'read', 'edit' ];
		}

		if ( $action === EntityPermissionChecker::ACTION_READ ) {
			return [ 'read' ];
		}

		throw new InvalidArgumentException( 'Unknown action to check permissions for: ' . $action );
	}

	private function mediawikiPermissionExists( $permission ) {
		return in_array( $permission, $this->availableRights );
	}

	private function getPermissionStatus( User $user,
		$permission,
		Title $title,
		$rigor = PermissionManager::RIGOR_SECURE
	) {
		$status = Status::newGood();

		$errors = $this->permissionManager->getPermissionErrors(
			$permission, $user, $title, $rigor );

		if ( $errors ) {
			$status->setResult( false );
			foreach ( $errors as $error ) {
				$status->fatal( ...$error );
			}
		}

		return $status;
	}

}

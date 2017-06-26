<?php

namespace Wikibase\Repo\Store;

use Status;
use Title;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * Checks permissions to do actions on the entity based on MediaWiki page permissions.
 *
 * @license GPL-2.0+
 */
class WikiPageEntityStorePermissionChecker implements EntityPermissionChecker {

	/**
	 * @var EntityNamespaceLookup
	 */
	private $namespaceLookup;

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	public function __construct( EntityNamespaceLookup $namespaceLookup, EntityTitleLookup $titleLookup ) {
		$this->namespaceLookup = $namespaceLookup;
		$this->titleLookup = $titleLookup;
	}

	public function getPermissionStatusForEntity( User $user, $permission, EntityDocument $entity, $quick = '' ) {
		$id = $entity->getId();
		$status = null;

		if ( $id === null ) {
			$entityType = $entity->getType();

			if ( $permission === 'edit' ) {
				// for editing a non-existing page, check the createpage permission
				return $this->getPermissionStatusForEntityType( $user, 'createpage', $entityType, $quick );
			}

			return $this->getPermissionStatusForEntityType( $user, $permission, $entityType, $quick );
		}

		return $this->getPermissionStatusForEntityId( $user, $permission, $id, $quick );
	}

	public function getPermissionStatusForEntityId( User $user, $permission, EntityId $entityId, $quick = '' ) {
		$title = $this->titleLookup->getTitleForId( $entityId );

		if ( $title === null ) {
			if ( $permission === 'edit' ) {
				return $this->getPermissionStatusForEntityType(
					$user,
					'createpage',
					$entityId->getEntityType(),
					$quick
				);
			}

			return $this->getPermissionStatusForEntityType(
				$user,
				$permission,
				$entityId->getEntityType(),
				$quick
			);
		}

		return $this->checkPermission( $user, $permission, $title, $entityId->getEntityType(), $quick );
	}

	public function getPermissionStatusForEntityType( User $user, $permission, $type, $quick = '' ) {
		$title = $this->getPageTitleInEntityNamespace( $type );

		if ( $permission === 'edit' ) {
			// Note: No entity ID given, assuming creating new entity, i.e. create permissions will be checked
			return $this->checkPermission(
				$user,
				'createpage',
				$title,
				$type,
				$quick
			);
		}

		return $this->checkPermission( $user, $permission, $title, $type, $quick );
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

	private function checkPermission( User $user, $permission, Title $title, $entityType, $quick ='' ) {
		$status = Status::newGood();

		$permissions = $this->getMediaWikiPermissionsToCheck( $permission, $entityType );

		foreach ( $permissions as $permission ) {
			$partialStatus = $this->getPermissionStatus( $user, $permission, $title, $quick );
			$status->merge( $partialStatus );
		}

		return $status;
	}

	private function getMediaWikiPermissionsToCheck( $permission, $entityType ) {
		if ( $permission === 'edit' ) {
			return [ 'read', 'edit' ]; // TODO: need to check read permission?
		}

		if ( $permission === 'createpage' || $permission === $entityType . '-create' ) {
			return [ 'read', 'edit', 'createpage', $entityType . '-create' ]; // TODO: need to check read permission?
		}

		return [ $permission ];
	}

	private function getPermissionStatus( User $user, $permission, Title $title, $quick = '' ) {
		$status = Status::newGood();

		$errors = $title->getUserPermissionsErrors( $permission, $user, $quick !== 'quick' );

		if ( $errors ) {
			$status->setResult( false );
			foreach ( $errors as $error ) {
				$status->fatal( $error );
			}
		}

		return $status;
	}

}

<?php

namespace Wikibase\Lib\Store;

use MWException;
use PermissionsError;
use Status;
use User;
use Wikibase\DataModel\Entity\EntityContainer;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\HierarchicalEntityId;

/**
 * A generic EntityStore implementing storage for entities that use an hierarchical addressing
 * scheme.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class HierarchicalEntityStore implements EntityStore {

	/**
	 * @var EntityStore
	 */
	private $store;

	/**
	 * @var EntityRevisionLookup
	 */
	private $lookup;

	/**
	 * @param EntityStore $store
	 * @param EntityRevisionLookup $lookup
	 */
	public function __construct( EntityStore $store, EntityRevisionLookup $lookup ) {
		$this->store = $store;
		$this->lookup = $lookup;
	}

	/**
	 * @param HierarchicalEntityId $id
	 *
	 * @throws StorageException
	 * @return EntityRevision
	 */
	private function getContainerRevision(
		HierarchicalEntityId $id,
		$revisionId = 0,
		$mode = EntityRevisionLookup::LATEST_FROM_MASTER
	) {
		$baseId = $id->getBaseId();
		$rev = $this->lookup->getEntityRevision( $baseId, $revisionId, $mode );

		if ( !$rev ) {
			throw new StorageException( 'Cannot resolve base entity ID ' . $baseId->getSerialization() );
		}

		if ( !( $rev->getEntity() instanceof  EntityContainer) ) {
			throw new StorageException( 'Cannot resolve ID ' . $id );
		}

		return $rev;
	}

	/**
	 * @param HierarchicalEntityId $id
	 *
	 * @throws StorageException
	 * @return EntityContainer|EntityDocument
	 */
	private function getContainer( HierarchicalEntityId $id ) {
		return $this->getContainerRevision( $id )->getEntity();
	}

	/**
	 * @see EntityStore::assignFreshId
	 *
	 * @param EntityDocument $entity
	 *
	 * @throws StorageException
	 */
	public function assignFreshId( EntityDocument $entity ) {
		// FIXME: we will have to know the ID of the parent entity here somehow!
		// Maybe support $entity->getParentId()?
		$this->store->assignFreshId( $entity );
	}

	/**
	 * @see EntityStore::saveEntity
	 *
	 * @param EntityDocument $entity the entity to save.
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
	 * @throws StorageException
	 * @throws PermissionsError
	 */
	public function saveEntity( EntityDocument $entity, $summary, User $user, $flags = 0, $baseRevId = false ) {
		if ( !$entity->getId() ) {
			if ( $flags & EDIT_NEW ) {
				$this->assignFreshId( $entity );
			} else {
				throw new StorageException( Status::newFatal( 'edit-gone-missing' ) );
			}
		}

		$id = $entity->getId();

		if ( $id instanceof HierarchicalEntityId ) {
			$rev = $this->getContainerRevision( $id, $baseRevId );
			$baseRevId = $rev->getRevisionId();
			$entityToSave = $rev->getEntity();
			$entityToSave->putEntity( $entity );
		} else {
			$entityToSave = $entity;
		}

		$this->store->saveEntity( $entityToSave, $summary, $user, $flags, $baseRevId );
	}

	/**
	 * @see EntityStore::saveRedirect
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
	 * @throws StorageException
	 * @throws PermissionsError
	 */
	public function saveRedirect( EntityRedirect $redirect, $summary, User $user, $flags = 0, $baseRevId = false ) {
		// FIXME: does it make sense to support redirects between sub-entities? Implement EntityRedirectContainer
		$this->store->saveEntity( $redirect, $summary, $user, $flags, $baseRevId );
	}

	/**
	 * @see EntityStore::deleteEntity
	 *
	 * @param EntityId $entityId
	 * @param string $reason the reason for deletion
	 * @param User $user
	 */
	public function deleteEntity( EntityId $entityId, $reason, User $user ) {
		if ( $entityId instanceof HierarchicalEntityId ) {
			$container = $this->getContainer( $entityId );
			$container->removeEntity( $entityId );
			$this->saveEntity( $container, $reason, $user, EDIT_UPDATE );
		} else {
			$this->store->deleteEntity( $entityId, $reason, $user );
		}
	}

	/**
	 * @see EntityStore::userWasLastToEdit
	 *
	 * @param User $user the user
	 * @param EntityId $id the entity to check
	 * @param int $lastRevId the revision to check from
	 *
	 * @return bool
	 */
	public function userWasLastToEdit( User $user, EntityId $id, $lastRevId ) {
		if ( $id instanceof HierarchicalEntityId ) {
			$id = $id->getRootId();
		}

		return $this->store->userWasLastToEdit( $user, $id, $lastRevId );
	}

	/**
	 * @see EntityStore::updateWatchlist
	 *
	 * @param User $user
	 * @param EntityId $id the entity to watch
	 * @param bool $watch whether to watch or unwatch the page.
	 *
	 * @throws MWException
	 * @return void
	 */
	public function updateWatchlist( User $user, EntityId $id, $watch ) {
		if ( $id instanceof HierarchicalEntityId ) {
			$id = $id->getRootId();
		}

		$this->store->updateWatchlist( $user, $id, $watch );
	}

	/**
	 * @see EntityStore::isWatching
	 *
	 * @param User $user
	 * @param EntityId $id the entity to watch
	 *
	 * @return bool
	 */
	public function isWatching( User $user, EntityId $id ) {
		if ( $id instanceof HierarchicalEntityId ) {
			$id = $id->getRootId();
		}

		return $this->store->isWatching( $user, $id );
	}

	/**
	 * @see EntityStore::canCreateWithCustomId
	 *
	 * @param EntityId $id
	 *
	 * @return bool
	 */
	public function canCreateWithCustomId( EntityId $id ) {
		if ( $id instanceof HierarchicalEntityId ) {
			$container = $this->getContainer( $id );
			return $container->canAddWithCustomId( $id );
		} else {
			$this->store->canCreateWithCustomId( $id );
		}
	}

}

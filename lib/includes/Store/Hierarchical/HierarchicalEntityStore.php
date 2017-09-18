<?php

namespace Wikibase\Lib\Store\Hierarchical;

use LogicException;
use MWException;
use PermissionsError;
use Status;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\StorageException;

/**
 * Wrapper for EntityStores implementing storage for entities that use an hierarchical addressing
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

	public function __construct( EntityStore $store, EntityRevisionLookup $lookup ) {
		$this->store = $store;
		$this->lookup = $lookup;
	}

	/**
	 * @see EntityStore::assignFreshId
	 *
	 * @param EntityDocument $entity
	 *
	 * @throws StorageException
	 */
	public function assignFreshId( EntityDocument $entity ) {
		if ( $entity->getId() instanceof HierarchicalEntityId ) {
			throw new LogicException( 'Not yet implemented for HierarchicalEntityIds' );
		}

		$this->store->assignFreshId( $entity );
	}

	/**
	 * @see EntityStore::saveEntity
	 *
	 * @param EntityDocument $entity
	 * @param string $summary
	 * @param User $user
	 * @param int $flags
	 * @param int|bool $baseRevId
	 *
	 * @throws StorageException
	 * @throws PermissionsError
	 * @return EntityRevision
	 */
	public function saveEntity( EntityDocument $entity, $summary, User $user, $flags = 0, $baseRevId = false ) {
		if ( !$entity->getId() ) {
			if ( !( $flags & EDIT_NEW ) ) {
				throw new StorageException( Status::newFatal( 'edit-gone-missing' ) );
			}

			$this->assignFreshId( $entity );
		}

		$id = $entity->getId();

		if ( $id instanceof HierarchicalEntityId ) {
			$parentRevision = $this->getParentRevision( $id, $baseRevId );

			/** @var HierarchicalEntityContainer $parent */
			$parent = $parentRevision->getEntity();
			$parent->setChildEntity( $entity );

			$entity = $parent;
			$baseRevId = $parentRevision->getRevisionId();
		}

		return $this->store->saveEntity( $entity, $summary, $user, $flags, $baseRevId );
	}

	/**
	 * @see EntityStore::saveRedirect
	 *
	 * @param EntityRedirect $redirect
	 * @param string $summary
	 * @param User $user
	 * @param int $flags
	 * @param int|bool $baseRevId
	 *
	 * @throws StorageException
	 * @throws PermissionsError
	 * @return int
	 */
	public function saveRedirect( EntityRedirect $redirect, $summary, User $user, $flags = 0, $baseRevId = false ) {
		if ( $redirect->getEntityId() instanceof HierarchicalEntityId ) {
			throw new LogicException( 'Not yet implemented for HierarchicalEntityIds' );
		}

		return $this->store->saveRedirect( $redirect, $summary, $user, $flags, $baseRevId );
	}

	/**
	 * @see EntityStore::deleteEntity
	 *
	 * @param EntityId $entityId
	 * @param string $reason
	 * @param User $user
	 */
	public function deleteEntity( EntityId $entityId, $reason, User $user ) {
		if ( !( $entityId instanceof HierarchicalEntityId ) ) {
			$this->store->deleteEntity( $entityId, $reason, $user );
			return;
		}

		/** @var HierarchicalEntityContainer $parent */
		$parent = $this->getParentRevision( $entityId )->getEntity();
		$parent->removeChildEntity( $entityId );
		$this->saveEntity( $parent, $reason, $user, EDIT_UPDATE );
	}

	/**
	 * @see EntityStore::userWasLastToEdit
	 *
	 * @param User $user
	 * @param EntityId $id
	 * @param int $lastRevId
	 *
	 * @return bool
	 */
	public function userWasLastToEdit( User $user, EntityId $id, $lastRevId ) {
		return $this->store->userWasLastToEdit( $user, $this->getBaseId( $id ), $lastRevId );
	}

	/**
	 * @see EntityStore::updateWatchlist
	 *
	 * @param User $user
	 * @param EntityId $id
	 * @param bool $watch
	 *
	 * @throws MWException
	 */
	public function updateWatchlist( User $user, EntityId $id, $watch ) {
		$this->store->updateWatchlist( $user, $this->getBaseId( $id ), $watch );
	}

	/**
	 * @see EntityStore::isWatching
	 *
	 * @param User $user
	 * @param EntityId $id
	 *
	 * @return bool
	 */
	public function isWatching( User $user, EntityId $id ) {
		return $this->store->isWatching( $user, $this->getBaseId( $id ) );
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
			throw new LogicException( 'Not yet implemented for HierarchicalEntityIds' );
		}

		$this->store->canCreateWithCustomId( $id );
	}

	/**
	 * @param EntityId $id
	 *
	 * @return EntityId
	 */
	private function getBaseId( EntityId $id ) {
		return $id instanceof HierarchicalEntityId ? $id->getParentId() : $id;
	}

	/**
	 * @param HierarchicalEntityId $id
	 * @param int $revisionId
	 *
	 * @throws StorageException
	 * @return EntityRevision guaranteed to contain a HierarchicalEntityContainer
	 */
	private function getParentRevision( HierarchicalEntityId $id, $revisionId = 0 ) {
		$parentId = $id->getParentId();
		$parent = $this->lookup->getEntityRevision(
			$parentId,
			$revisionId,
			EntityRevisionLookup::LATEST_FROM_MASTER
		);

		if ( !$parent ) {
			throw new StorageException( 'Cannot resolve parent ID ' . $parentId->getSerialization() );
		}

		if ( !( $parent->getEntity() instanceof HierarchicalEntityContainer ) ) {
			throw new StorageException( 'Cannot resolve ID ' . $id->getSerialization() );
		}

		return $parent;
	}

	/**
	 * @deprecated This is only here to overcome a violation introduced in
	 *  https://gerrit.wikimedia.org/r/357812 as part of https://phabricator.wikimedia.org/T162533
	 *
	 * @param EntityId $entityId
	 *
	 * @throws LogicException
	 * @return \WikiPage
	 */
	public function getWikiPageForEntity( EntityId $entityId ) {
		if ( !method_exists( $this->store, 'getWikiPageForEntity' )
			|| !defined( 'MW_PHPUNIT_TEST' )
		) {
			throw new LogicException( 'Do not use' );
		}

		return $this->store->getWikiPageForEntity( $entityId );
	}

}

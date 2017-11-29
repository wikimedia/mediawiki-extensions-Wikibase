<?php

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
use MWException;
use PermissionsError;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;

/**
 * An EntityStore that does dispatching based on the entity type.
 *
 * Warning! This class is build on the assumption that it is only instantiated once.
 *
 * @license GPL-2.0+
 * @author Thiemo Kreuz
 */
class TypeDispatchingEntityStore implements EntityStore {

	/**
	 * @var EntityStore[] indexed by entity type
	 */
	private $stores;

	/**
	 * @var EntityStore
	 */
	private $defaultStore;

	/**
	 * @param callable[] $callbacks indexed by entity type
	 * @param EntityStore $defaultStore
	 * @param EntityRevisionLookup $lookup
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		array $callbacks,
		EntityStore $defaultStore,
		EntityRevisionLookup $lookup
	) {
		foreach ( $callbacks as $entityType => $callback ) {
			$store = call_user_func( $callback, [ $defaultStore, $lookup ] );

			if ( !( $store instanceof EntityStore ) ) {
				throw new InvalidArgumentException(
					"Callback provided for $entityType did not created an EntityStore"
				);
			}

			$this->stores[$entityType] = $store;
		}

		$this->defaultStore = $defaultStore;
	}

	/**
	 * @see EntityStore::assignFreshId
	 *
	 * @param EntityDocument $entity
	 *
	 * @throws StorageException
	 */
	public function assignFreshId( EntityDocument $entity ) {
		$this->getStore( $entity->getType() )->assignFreshId( $entity );
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
	public function saveEntity(
		EntityDocument $entity,
		$summary,
		User $user,
		$flags = 0,
		$baseRevId = false
	) {
		$this->getStore( $entity->getType() )->saveEntity(
			$entity,
			$summary,
			$user,
			$flags,
			$baseRevId
		);
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
	public function saveRedirect(
		EntityRedirect $redirect,
		$summary,
		User $user,
		$flags = 0,
		$baseRevId = false
	) {
		$this->getStore( $redirect->getEntityId()->getEntityType() )->saveRedirect(
			$redirect,
			$summary,
			$user,
			$flags,
			$baseRevId
		);
	}

	/**
	 * @see EntityStore::deleteEntity
	 *
	 * @param EntityId $entityId
	 * @param string $reason
	 * @param User $user
	 */
	public function deleteEntity( EntityId $entityId, $reason, User $user ) {
		$this->getStore( $entityId->getEntityType() )->deleteEntity( $entityId, $reason, $user );
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
		$this->getStore( $id->getEntityType() )->userWasLastToEdit( $user, $id, $lastRevId );
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
		$this->getStore( $id->getEntityType() )->updateWatchlist( $user, $id, $watch );
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
		$this->getStore( $id->getEntityType() )->isWatching( $user, $id );
	}

	/**
	 * @see EntityStore::canCreateWithCustomId
	 *
	 * @param EntityId $id
	 *
	 * @return bool
	 */
	public function canCreateWithCustomId( EntityId $id ) {
		$this->getStore( $id->getEntityType() )->canCreateWithCustomId( $id );
	}

	/**
	 * @param string $entityType
	 *
	 * @return EntityStore
	 */
	private function getStore( $entityType ) {
		return $this->stores[$entityType] ?: $this->defaultStore;
	}

}

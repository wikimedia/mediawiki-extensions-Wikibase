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
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class TypeDispatchingEntityStore implements EntityStore {

	/**
	 * @var array indexed by entity type
	 */
	private $stores;

	/**
	 * @var EntityStore
	 */
	private $defaultStore;

	/**
	 * @var EntityRevisionLookup
	 */
	private $lookup;

	/**
	 * @param callable[] $callbacks indexed by entity type
	 * @param EntityStore $defaultStore
	 * @param EntityRevisionLookup $lookup
	 */
	public function __construct(
		array $callbacks,
		EntityStore $defaultStore,
		EntityRevisionLookup $lookup
	) {
		$this->stores = $callbacks;
		$this->defaultStore = $defaultStore;
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
	 * @param string[] $tags
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
		$baseRevId = false,
		array $tags = []
	) {
		return $this->getStore( $entity->getType() )->saveEntity(
			$entity,
			$summary,
			$user,
			$flags,
			$baseRevId,
			$tags
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
	 * @return int The new revision ID
	 */
	public function saveRedirect(
		EntityRedirect $redirect,
		$summary,
		User $user,
		$flags = 0,
		$baseRevId = false,
		array $tags = []
	) {
		return $this->getStore( $redirect->getEntityId()->getEntityType() )->saveRedirect(
			$redirect,
			$summary,
			$user,
			$flags,
			$baseRevId,
			$tags
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
		return $this->getStore( $id->getEntityType() )->userWasLastToEdit( $user, $id, $lastRevId );
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
		return $this->getStore( $id->getEntityType() )->isWatching( $user, $id );
	}

	/**
	 * @see EntityStore::canCreateWithCustomId
	 *
	 * @param EntityId $id
	 *
	 * @return bool
	 */
	public function canCreateWithCustomId( EntityId $id ) {
		return $this->getStore( $id->getEntityType() )->canCreateWithCustomId( $id );
	}

	/**
	 * @param string $entityType
	 *
	 * @throws InvalidArgumentException
	 * @return EntityStore
	 */
	private function getStore( $entityType ) {
		if ( !array_key_exists( $entityType, $this->stores ) ) {
			return $this->defaultStore;
		}

		if ( is_callable( $this->stores[$entityType] ) ) {
			$this->stores[$entityType] = call_user_func(
				$this->stores[$entityType],
				$this->defaultStore,
				$this->lookup
			);

			if ( !( $this->stores[$entityType] instanceof EntityStore ) ) {
				throw new InvalidArgumentException(
					"Callback provided for $entityType did not create an EntityStore"
				);
			}
		}

		return $this->stores[$entityType];
	}

	/**
	 * @internal
	 * @deprecated This is only here to overcome a violation introduced in
	 *  https://gerrit.wikimedia.org/r/357812 as part of https://phabricator.wikimedia.org/T162533
	 *
	 * @param EntityId $entityId
	 *
	 * @throws \LogicException
	 * @return \WikiPage
	 */
	public function getWikiPageForEntity( EntityId $entityId ) {
		$store = $this->getStore( $entityId->getEntityType() );

		if ( !method_exists( $store, 'getWikiPageForEntity' )
			|| !defined( 'MW_PHPUNIT_TEST' )
		) {
			throw new \LogicException( 'Do not use' );
		}

		// @phan-suppress-next-line PhanUndeclaredMethod
		return $store->getWikiPageForEntity( $entityId );
	}

}

<?php

namespace Wikibase\Test;

use DatabaseBase;
use User;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\StorageException;

/**
 * Mock repository for use in tests.
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class MockRepositoryIndexes implements EntityStore {

	/**
	 * @var MockRepository
	 */
	protected $repository;

	/**
	 * "$globalSiteId:$pageTitle" => item id integer
	 *
	 * @var string[]
	 */
	protected $itemByLink = array();

	/**
	 * @return MockRepository
	 */
	public function getRepository() {
		return $this->repository;
	}

	/**
	 * Returns an array with the conflicts between the item and the sitelinks
	 * currently in the store. The array is empty if there are no such conflicts.
	 *
	 * The items in the return array are arrays with the following elements:
	 * - integer itemId
	 * - string siteId
	 * - string sitePage
	 *
	 * @param Item               $item
	 * @param DatabaseBase|null $db The database object to use (optional).
	 *                               If conflict checking is performed as part of a save operation,
	 *                               this should be used to provide the master DB connection that will
	 *                               also be used for saving. This will preserve transactional integrity
	 *                               and avoid race conditions.
	 *
	 * @return array of array
	 */
	public function getConflictsForItem( Item $item, DatabaseBase $db = null ) {
		$newLinks = array();

		foreach ( $item->getSiteLinks() as $siteLink ) {
			$newLinks[$siteLink->getSiteId()] = $siteLink->getPageName();
		}

		$conflicts = array();
		$entities = $this->repository->getEntities(); //FIXME

		foreach ( array_keys( $entities ) as $id ) {
			$id = $this->parseId( $id );

			if ( $id->getEntityType() !== Item::ENTITY_TYPE ) {
				continue;
			}

			$oldLinks = $this->getLinks( array( $id->getNumericId() ) );

			foreach ( $oldLinks as $link ) {
				list( $wiki, $page, $itemId ) = $link;

				if ( $item->getId() && $itemId === $item->getId()->getNumericId() ) {
					continue;
				}

				if ( isset( $newLinks[$wiki] ) ) {
					if ( $page == $newLinks[$wiki] ) {
						$conflicts[] = $link;
					}
				}
			}
		}

		return $conflicts;
	}

	/**
	 * Returns the id of the item that is equivalent to the
	 * provided page, or null if there is none.
	 *
	 * @since 0.1
	 *
	 * @param string $globalSiteId
	 * @param string $pageTitle
	 *
	 * @return ItemId|null
	 */
	public function getItemIdForLink( $globalSiteId, $pageTitle ) {
		// We store page titles with spaces instead of underscores
		$pageTitle = str_replace( '_', ' ', $pageTitle );

		$key = "$globalSiteId:$pageTitle";

		if ( isset( $this->itemByLink[$key] ) ) {
			return ItemId::newFromNumber( $this->itemByLink[$key] );
		} else {
			return null;
		}
	}

	/**
	 * @see SiteLinkLookup::getEntityIdForSiteLink
	 *
	 * @since 0.4
	 *
	 * @param SiteLink $siteLink
	 *
	 * @return ItemId|null
	 */
	public function getEntityIdForSiteLink( SiteLink $siteLink ) {
		$globalSiteId = $siteLink->getSiteId();
		$pageName = $siteLink->getPageName();

		// @todo: fix test data to use titles with underscores, like the site link table does it
		$title = \Title::newFromText( $pageName );
		$pageTitle = $title->getDBkey();

		return $this->getItemIdForLink( $globalSiteId, $pageTitle );
	}

	/**
	 * Registers the sitelinsk of the given Item so they can later be found with getLinks, etc
	 *
	 * @param Item $item
	 */
	protected function registerSiteLinks( Item $item ) {
		$this->unregisterSiteLinks( $item );

		$numId = $item->getId()->getNumericId();

		foreach ( $item->getSiteLinks() as $siteLink ) {
			$key = $siteLink->getSiteId() . ':' . $siteLink->getPageName();
			$this->itemByLink[$key] = $numId;
		}
	}

	/**
	 * Unregisters the sitelinsk of the given Item so they are no longer found with getLinks, etc
	 *
	 * @param Item $item
	 */
	protected function unregisterSiteLinks( Item $item ) {
		// clean up old sitelinks

		$numId = $item->getId()->getNumericId();

		foreach ( $this->itemByLink as $key => $n ) {
			if ( $n === $numId ) {
				unset( $this->itemByLink[$key] );
			}
		}
	}

	/**
	 * Puts an entity into the mock repository. If there already is an entity with the same ID
	 * in the mock repository, it is not removed, but replaced as the current one. If a revision
	 * ID is given, the entity with the highest revision ID is considered the current one.
	 *
	 * @param Entity $entity
	 * @param int $revision
	 * @param int|string $timestamp
	 * @param User|string|null $user
	 *
	 * @return EntityRevision
	 */
	public function putEntity( Entity $entity, $revision = 0, $timestamp = 0, $user = null ) {
		$revision = $this->repository->putEntity( $entity, $revision, $timestamp, $user );

		//FIXME: unregister old sitelinks!

		if ( $entity instanceof Item ) {
			$this->registerSiteLinks( $entity );
		}

		return $revision;
	}

	/**
	 * Puts a redirect into the mock repository. If there already is an entity with the same ID
	 * in the mock repository, it is replaced with the redirect.
	 *
	 * @param EntityRedirect $redirect
	 */
	public function putRedirect( EntityRedirect $redirect ) {
		$this->repository->putRedirect( $redirect );
	}

	/**
	 * Removes an entity from the mock repository.
	 *
	 * @param EntityId $id
	 *
	 * @return Entity
	 */
	public function removeEntity( EntityId $id ) {
		try {
			$oldEntity = $this->repository->getEntity( $id );

			if ( $oldEntity && ( $oldEntity instanceof Item ) ) {
				// clean up old sitelinks
				$this->unregisterSiteLinks( $oldEntity );
			}
		} catch ( StorageException $ex ) {
			// ignore
		}

		$oldEntity = $this->repository->removeEntity( $id );
		return $oldEntity;
	}

	/**
	 * Returns how many links match the provided conditions.
	 *
	 * Note: this is an exact count which is expensive if the result set is big.
	 * This means you probably do not want to call this method without any conditions.
	 *
	 * @since 0.3
	 *
	 * @param array $itemIds
	 * @param array $siteIds
	 * @param array $pageNames
	 *
	 * @return integer
	 */
	public function countLinks( array $itemIds, array $siteIds = array(), array $pageNames = array() ) {
		return count( $this->getLinks( $itemIds, $siteIds, $pageNames ) );
	}

	/**
	 * Returns the links that match the provided conditions.
	 * The links are returned as arrays with the following elements in specified order:
	 * - siteId
	 * - pageName
	 * - itemId (unprefixed)
	 *
	 * Note: if the conditions are not very selective the result set can be very big.
	 * Thus the caller is responsible for not executing to expensive queries in it's context.
	 *
	 * @since 0.3
	 *
	 * @param array $itemIds
	 * @param array $siteIds
	 * @param array $pageNames
	 *
	 * @return array[]
	 */
	public function getLinks( array $itemIds, array $siteIds = array(), array $pageNames = array() ) {
		$links = array();

		$entities = $this->repository->getEntities(); //FIXME

		/* @var Entity $entity */
		foreach ( array_keys( $entities ) as $id ) {
			$id = $this->parseId( $id ); //FIXME

			if ( $id->getEntityType() !== Item::ENTITY_TYPE ) {
				continue;
			}

			if ( !empty( $itemIds ) && !in_array( $id->getNumericId(), $itemIds ) ) {
				continue;
			}

			/**
			 * @var Item $entity
			 */
			$entity = $this->getEntity( $id ); //FIXME

			foreach ( $entity->getSiteLinks() as $link ) {
				if ( $this->linkMatches( $entity, $link, $itemIds, $siteIds, $pageNames ) ) {
					$links[] = array(
						$link->getSiteId(),
						$link->getPageName(),
						$entity->getId()->getNumericId(),
					);
				}
			}
		}

		return $links;
	}

	/**
	 * Returns true if the link matches the given conditions.
	 *
	 * @param Item     $item
	 * @param SiteLink $link
	 * @param array              $itemIds
	 * @param array              $siteIds
	 * @param array              $pageNames
	 *
	 * @return bool
	 */
	protected function linkMatches( Item $item, SiteLink $link,
		array $itemIds, array $siteIds = array(), array $pageNames = array() ) {

		if ( !empty( $itemIds ) ) {
			if ( !in_array( $item->getId()->getNumericId(), $itemIds ) ) {
				return false;
			}
		}

		if ( !empty( $siteIds ) ) {
			if ( !in_array( $link->getSiteId(), $siteIds ) ) {
				return false;
			}
		}

		if ( !empty( $pageNames ) ) {
			if ( !in_array( $link->getPageName(), $pageNames ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @see SiteLinkLookup::getSiteLinksForItem
	 *
	 * Returns an array of SiteLink for an EntityId.
	 *
	 * If the entity isn't known or not an Item, an empty array is returned.
	 *
	 * @since 0.4
	 *
	 * @param ItemId $itemId
	 *
	 * @return SiteLink[]
	 */
	public function getSiteLinksForItem( ItemId $itemId ) {
		$entity = $this->getEntity( $itemId );

		if ( $entity instanceof Item ) {
			return $entity->getSiteLinks();
		}

		// FIXME: throw InvalidArgumentException rather then failing silently
		return array();
	}

	/**
	 * Stores the given Entity.
	 *
	 * @param Entity $entity the entity to save.
	 * @param string $summary ignored
	 * @param User $user ignored
	 * @param int $flags EDIT_XXX flags, as defined for WikiPage::doEditContent.
	 * @param int|bool $baseRevId the revision ID $entity is based on. Saving should fail if
	 * $baseRevId is no longer the current revision.
	 *
	 * @see WikiPage::doEditContent
	 *
	 * @return EntityRevision
	 *
	 * @throws StorageException
	 */
	public function saveEntity( Entity $entity, $summary, User $user, $flags = 0, $baseRevId = false ) {
		$entityRevision = $this->repository->saveEntity( $entity, $summary, $user, $flags, $baseRevId );

		//FIXME: unregister old sitelinks!

		if ( $entity instanceof Item ) {
			$this->registerSiteLinks( $entity );
		}

		return $entityRevision;
	}

	/**
	 * @see EntityStore::saveRedirect
	 *
	 * @param EntityRedirect $redirect
	 * @param string $summary
	 * @param User $user
	 * @param int $flags
	 * @param bool $baseRevId
	 *
	 * @throws StorageException Always
	 * @return int Never
	 */
	public function saveRedirect( EntityRedirect $redirect, $summary, User $user, $flags = 0, $baseRevId = false ) {
		//FIXME: unregister links if the entity existed!
		return $this->repository->saveRedirect( $redirect, $summary, $user, $flags, $baseRevId );
	}

	/**
	 * Deletes the given entity in some underlying storage mechanism.
	 *
	 * @param EntityId $entityId
	 * @param string $reason the reason for deletion
	 * @param User $user
	 */
	public function deleteEntity( EntityId $entityId, $reason, User $user ) {
		$this->removeEntity( $entityId );
	}

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
	public function assignFreshId( Entity $entity ) {
		$this->repository->assignFreshId( $entity );
	}

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
	public function userWasLastToEdit( User $user, EntityId $id, $lastRevId ) {
		return $this->repository->userWasLastToEdit( $user, $id, $lastRevId );
	}

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
	public function updateWatchlist( User $user, EntityId $id, $watch ) {
		$this->repository->updateWatchlist( $user, $id, $watch );
	}

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
	public function isWatching( User $user, EntityId $id ) {
		return $this->repository->isWatching( $user, $id );
	}

}

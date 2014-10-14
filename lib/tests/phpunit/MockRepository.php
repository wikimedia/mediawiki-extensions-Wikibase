<?php

namespace Wikibase\Test;

use DatabaseBase;
use Status;
use User;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Entity\PropertyNotFoundException;
use Wikibase\DataModel\SiteLink;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\GenericEntityInfoBuilder;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\Store\TermsLookup;
use Wikibase\Lib\Store\UnresolvedRedirectException;

/**
 * Mock repository for use in tests.
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class MockRepository implements
	EntityInfoBuilderFactory,
	EntityLookup,
	EntityRevisionLookup,
	EntityStore,
	PropertyDataTypeLookup,
	SiteLinkLookup,
	TermsLookup
{

	/**
	 * Entity id serialization => array of EntityRevision
	 *
	 * @var array[]
	 */
	protected $entities = array();

	/**
	 * Log entries. Each entry has the following fields:
	 * revision, entity, summary, user
	 *
	 * @var array
	 */
	protected $log = array();

	/**
	 * Entity id serialization => EntityRedirect
	 *
	 * @var EntityRedirect[]
	 */
	protected $redirects = array();

	/**
	 * User ID + Entity Id -> bool
	 *
	 * @var array[]
	 */
	protected $watchlist = array();

	/**
	 * "$globalSiteId:$pageTitle" => item id integer
	 *
	 * @var string[]
	 */
	protected $itemByLink = array();

	/**
	 * @var int
	 */
	private $maxId = 0;

	/**
	 * @var int
	 */
	private $maxRev = 0;

	/**
	 * @see EntityLookup::getEntity
	 *
	 * @param EntityID $entityId
	 *
	 * @return Entity|null
	 *
	 * @throw StorageException
	 */
	public function getEntity( EntityId $entityId ) {
		$rev = $this->getEntityRevision( $entityId );

		return $rev === null ? null : $rev->getEntity()->copy();
	}

	/**
	 * @since 0.4
	 * @see EntityRevisionLookup::getEntityRevision
	 *
	 * @param EntityID $entityId
	 * @param int $revision The desired revision id, 0 means "current".
	 *
	 * @throws StorageException
	 * @return EntityRevision|null
	 * @throw StorageException
	 */
	public function getEntityRevision( EntityId $entityId, $revision = 0 ) {
		$key = $entityId->getSerialization();

		if ( isset( $this->redirects[$key] ) ) {
			throw new UnresolvedRedirectException( $this->redirects[$key]->getTargetId() );
		}

		if ( !isset( $this->entities[$key] ) || empty( $this->entities[$key] ) ) {
			return null;
		}

		if ( $revision === false ) { // default changed from false to 0
			wfWarn( 'getEntityRevision() called with $revision = false, use 0 instead.' );
			$revision = 0;
		}

		/* @var EntityRevision[] $revisions */
		$revisions = $this->entities[$key];

		if ( $revision === 0 ) { // note: be robust and accept false too.
			$revIds = array_keys( $revisions );
			$n = count( $revIds );

			$revision = $revIds[$n-1];
		} else if ( !isset( $revisions[$revision] ) ) {
			throw new StorageException( "no such revision for entity $key: $revision" );
		}

		$entityRev = $revisions[$revision];
		$entityRev = new EntityRevision( // return a copy!
			$entityRev->getEntity()->copy(), // return a copy!
			$entityRev->getRevision(),
			$entityRev->getTimestamp()
		);

		return $entityRev;
	}

	/**
	 * See EntityLookup::hasEntity()
	 *
	 * @since 0.4
	 *
	 * @param EntityID $entityId
	 *
	 * @return bool
	 */
	public function hasEntity( EntityId $entityId ) {
		return $this->getEntity( $entityId ) !== null;
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

		foreach ( array_keys( $this->entities ) as $id ) {
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
		if ( $entity->getId() === null ) {
			$this->assignFreshId( $entity );
		}

		$oldEntity = $this->getEntity( $entity->getId() );

		if ( $oldEntity && ( $oldEntity instanceof Item ) ) {
			// clean up old sitelinks
			$this->unregisterSiteLinks( $oldEntity );
		}

		if ( $entity instanceof Item ) {
			// add new sitelinks
			$this->registerSiteLinks( $entity );
		}

		$key = $entity->getId()->getSerialization();

		if ( !array_key_exists( $key, $this->entities ) ) {
			$this->entities[$key] = array();
		}

		if ( $revision === 0 ) {
			$revision = $this->maxRev +1;
		}

		$this->maxId = max( $this->maxId, $entity->getId()->getNumericId() );
		$this->maxRev = max( $this->maxRev, $revision );

		$rev = new EntityRevision(
			$entity->copy(), // note: always clone
			$revision,
			wfTimestamp( TS_MW, $timestamp )
		);

		if ( $user !== null ) {
			if ( $user instanceof User ) {
				$user = $user->getName();
			}

			// just glue the user on here...
			$rev->user = $user;
		}

		unset( $this->redirects[$key] );

		$this->entities[$key][$revision] = $rev;
		ksort( $this->entities[$key] );

		return $rev;
	}

	/**
	 * Puts a redirect into the mock repository. If there already is an entity with the same ID
	 * in the mock repository, it is replaced with the redirect.
	 *
	 * @param EntityRedirect $redirect
	 */
	public function putRedirect( EntityRedirect $redirect ) {
		$key = $redirect->getEntityId()->getSerialization();

		if ( isset( $this->entities[$key] ) ) {
			$this->removeEntity( $redirect->getEntityId() );
		}

		$this->redirects[$key] = $redirect;
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
			$oldEntity = $this->getEntity( $id );

			if ( $oldEntity && ( $oldEntity instanceof Item ) ) {
				// clean up old sitelinks
				$this->unregisterSiteLinks( $oldEntity );
			}
		} catch ( StorageException $ex ) {
			$oldEntity = null; // ignore
		}

		$key = $id->getSerialization();
		unset( $this->entities[$key] );
		unset( $this->redirects[$key] );

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

		/* @var Entity $entity */
		foreach ( array_keys( $this->entities ) as $id ) {
			$id = $this->parseId( $id );

			if ( $id->getEntityType() !== Item::ENTITY_TYPE ) {
				continue;
			}

			if ( !empty( $itemIds ) && !in_array( $id->getNumericId(), $itemIds ) ) {
				continue;
			}

			/**
			 * @var Item $entity
			 */
			$entity = $this->getEntity( $id );

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
	 * Fetches the entities with provided ids and returns them.
	 * The result array contains the prefixed entity ids as keys.
	 * The values are either an Entity or null, if there is no entity with the associated id.
	 *
	 * The revisions can be specified as an array holding an integer element for each
	 * id in the $entityIds array or false for latest. If all should be latest, false
	 * can be provided instead of an array.
	 *
	 * @since 0.4
	 *
	 * @param EntityID[] $entityIds
	 *
	 * @return Entity|null[]
	 */
	public function getEntities( array $entityIds ) {
		$entities = array();

		foreach ( $entityIds as $entityId ) {
			if ( is_string( $entityId ) ) {
				$entityId = $this->parseId( $entityId );
			}

			$entities[$entityId->getSerialization()] = $this->getEntity( $entityId );
		}

		return $entities;
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

	public function getPropertyByLabel( $propertyLabel, $langCode ) {
		$ids = array_keys( $this->entities );

		foreach ( $ids as $id ) {
			$id = $this->parseId( $id );
			$entity = $this->getEntity( $id );

			if ( $entity->getType() !== Property::ENTITY_TYPE ) {
				continue;
			}

			$labels = $entity->getLabels( array( $langCode) );

			if ( empty( $labels ) ) {
				continue;
			}

			$label = reset( $labels );
			if ( $label !== $propertyLabel ) {
				continue;
			}

			return $entity;
		}

		return null;
	}

	/**
	 * @param array $ids
	 *
	 * @return GenericEntityInfoBuilder
	 */
	public function newEntityInfoBuilder( array $ids ) {
		return new GenericEntityInfoBuilder( $ids, new BasicEntityIdParser(), $this );
	}

	/**
	 * @see PropertyDataTypeLookup::getDataTypeIdForProperty()
	 *
	 * @since 0.5
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return string
	 * @throws PropertyNotFoundException
	 */
	public function getDataTypeIdForProperty( PropertyId $propertyId ) {
		/* @var Property $property */
		$property = $this->getEntity( $propertyId );

		if ( !$property ) {
			throw new PropertyNotFoundException( $propertyId );
		}

		return $property->getDataTypeId();
	}

	/**
	 * Returns the id of the latest revision of the given entity, or false if there is no such entity.
	 *
	 * @param EntityID $entityId
	 *
	 * @return int|false
	 */
	public function getLatestRevisionId( EntityId $entityId ) {
		$rev = $this->getEntityRevision( $entityId );

		return $rev === null ? false : $rev->getRevision();
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
		$id = $entity->getId();

		$status = Status::newGood();

		if ( ( $flags & EDIT_NEW ) > 0 && $id && $this->hasEntity( $id ) ) {
			$status->fatal( 'edit-already-exists' );
		}

		if ( ( $flags & EDIT_UPDATE ) > 0 && !$this->hasEntity( $id ) ) {
			$status->fatal( 'edit-gone-missing' );
		}

		if ( $baseRevId !== false && !$this->hasEntity( $id ) ) {
			//TODO: find correct message key to use with status??
			throw new StorageException( 'No base revision found for ' . $id->getSerialization() );
		}

		if ( $baseRevId !== false && $this->getEntityRevision( $id )->getRevision() !== $baseRevId ) {
			$status->fatal( 'edit-conflict' );
		}

		if ( !$status->isOK() ) {
			throw new StorageException( $status );
		}

		$entityRevision = $this->putEntity( $entity, 0, 0, $user );

		$this->putLog( $entityRevision->getRevision(), $entity->getId(), $summary, $user->getName() );
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
	 * @throws StorageException If the given type of entity does not support redirects
	 * @return int The revision id created by storing the redirect
	 */
	public function saveRedirect( EntityRedirect $redirect, $summary, User $user, $flags = 0, $baseRevId = false ) {
		if ( $redirect->getEntityId()->getEntityType() !== Item::ENTITY_TYPE ) {
			throw new StorageException( 'Entity type does not support redirects: ' . $redirect->getEntityId()->getEntityType() );
		}

		$this->putRedirect( $redirect );

		$revId = ++$this->maxRev;
		$this->putLog( $revId, $redirect->getEntityId(), $summary, $user->getName() );

		return $revId;
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
		$key = $id->getSerialization();
		if ( !isset( $this->entities[$key] ) ) {
			return false;
		}

		foreach ( $this->entities[$key] as $rev ) {
			if ( $rev->getRevision() >= $lastRevId ) {
				if ( isset( $rev->user ) && $rev->user !== $user->getName() ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Watches or unwatches the entity.
	 *
	 * @param User $user
	 * @param EntityId $id the entity to watch
	 * @param bool $watch whether to watch or unwatch the page.
	 */
	public function updateWatchlist( User $user, EntityId $id, $watch ) {
		if ( $watch ) {
			$this->watchlist[ $user->getName() ][ $id->getSerialization() ] = true;
		} else {
			unset( $this->watchlist[ $user->getName() ][ $id->getSerialization() ] );
		}
	}

	/**
	 * Determines whether the given user is watching the given item
	 *
	 * @param User $user
	 * @param EntityId $id the entity to watch
	 *
	 * @return bool
	 */
	public function isWatching( User $user, EntityId $id ) {
		return isset( $this->watchlist[ $user->getName() ][ $id->getSerialization() ] );
	}

	/**
	 * @see EntityStore::assignFreshId()
	 *
	 * @param Entity $entity
	 *
	 * @throws StorageException
	 */
	public function assignFreshId( Entity $entity ) {
		//TODO: Find a canonical way to generate an EntityId from the maxId number.
		//XXX: Using setId() with an integer argument is deprecated!
		$this->maxId++;
		$entity->setId( $this->maxId );
	}

	private function parseId( $id ) {
		$parser = new BasicEntityIdParser();
		return $parser->parse( $id );
	}

	/**
	 * @param int $revId
	 * @param EntityId|string $entityId
	 * @param string $summary
	 * @param User|string $user
	 */
	private function putLog( $revId, $entityId, $summary, $user ) {
		if ( $entityId instanceof EntityId ) {
			$entityId = $entityId->getSerialization();
		}

		if ( $user instanceof User ) {
			$user = $user->getName();
		}

		$this->log[$revId] = array(
			'revision' => intval( $revId ),
			'entity' => $entityId,
			'summary' => $summary,
			'user' => $user,
		);
	}

	/**
	 * Returns the log entry for the given revision Id.
	 *
	 * @param $revisionId
	 *
	 * @return array|null An associative array containing the fields
	 * 'revision', 'entity', 'summary', and 'user'.
	 */
	public function getLogEntry( $revisionId ) {
		return isset( $this->log[ $revisionId ] ) ? $this->log[ $revisionId ] : null;
	}

	/**
	 * Returns the newest (according to the revision id) log entry
	 * for the given entity.
	 *
	 * @param EntityId|string $entityId
	 *
	 * @return array|null An associative array containing the fields
	 * 'revision', 'entity', 'summary', and 'user'.
	 */
	public function getLatestLogEntryFor( $entityId ) {
		if ( $entityId instanceof EntityId ) {
			$entityId = $entityId->getSerialization();
		}

		// log entries by revision id, largest id first.
		$log = $this->log;
		krsort( $log );

		foreach ( $log as $entry ) {
			if ( $entry['entity'] === $entityId ) {
				return $entry;
			}
		}

		return null;
	}

	/**
	 * @see TermsLookup::getTermsByTermType
	 */
	public function getTermsByTermType( EntityId $entityId, $termType ) {
		try {
			$entity = $this->getEntity( $entityId );
		} catch ( UnresolvedRedirectException $ex ) {
			return array();
		}

		if ( !$entity ) {
			return array();
		}

		if ( $termType === 'label' ) {
			return $entity->getLabels();
		} elseif ( $termType === 'description' ) {
			return $entity->getDescriptions();
		}
	}

}

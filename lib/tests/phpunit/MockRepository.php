<?php

namespace Wikibase\Test;

use DatabaseBase;
use Status;
use Title;
use User;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Entity\PropertyNotFoundException;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityRevisionBatchLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\GenericEntityInfoBuilder;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\Store\UnresolvedRedirectException;

/**
 * Mock repository for use in tests.
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Thiemo Mättig
 */
class MockRepository implements
	EntityInfoBuilderFactory,
	EntityLookup,
	EntityRevisionLookup,
	EntityRevisionBatchLookup,
	EntityStore,
	PropertyDataTypeLookup,
	SiteLinkLookup
{

	/**
	 * Entity id serialization => array of EntityRevision
	 *
	 * @var array[]
	 */
	private $entities = array();

	/**
	 * Log entries. Each entry has the following fields:
	 * revision, entity, summary, user
	 *
	 * @var array[]
	 */
	private $log = array();

	/**
	 * Entity id serialization => EntityRedirect
	 *
	 * @var EntityRedirect[]
	 */
	private $redirects = array();

	/**
	 * User ID + Entity Id -> bool
	 *
	 * @var bool[]
	 */
	private $watchlist = array();

	/**
	 * "$globalSiteId:$pageTitle" => item id integer
	 *
	 * @var string[]
	 */
	private $itemByLink = array();

	/**
	 * @var int
	 */
	private $maxEntityId = 0;

	/**
	 * @var int
	 */
	private $maxRevisionId = 0;

	/**
	 * @see EntityLookup::getEntity
	 *
	 * @param EntityId $entityId
	 *
	 * @return EntityDocument|null
	 *
	 * @throws StorageException
	 */
	public function getEntity( EntityId $entityId ) {
		$revision = $this->getEntityRevision( $entityId );

		return $revision === null ? null : $revision->getEntity()->copy();
	}

	/**
	 * @since 0.4
	 * @see EntityRevisionLookup::getEntityRevision
	 *
	 * @param EntityId $entityId
	 * @param int|string $revisionId The desired revision id, or LATEST_FROM_SLAVE or LATEST_FROM_MASTER.
	 *
	 * @throws StorageException
	 * @return EntityRevision|null
	 */
	public function getEntityRevision( EntityId $entityId, $revisionId = self::LATEST_FROM_SLAVE ) {
		$key = $entityId->getSerialization();

		if ( isset( $this->redirects[$key] ) ) {
			throw new UnresolvedRedirectException( $this->redirects[$key]->getTargetId() );
		}

		if ( empty( $this->entities[$key] ) ) {
			return null;
		}

		// default changed from false to 0 and then to LATEST_FROM_SLAVE
		if ( $revisionId === false || $revisionId === 0 ) {
			wfWarn( 'getEntityRevision() called with $revisionId = false or 0, ' .
				'use EntityRevisionLookup::LATEST_FROM_SLAVE or EntityRevisionLookup::LATEST_FROM_MASTER instead.' );
			$revisionId = self::LATEST_FROM_SLAVE;
		}

		/** @var EntityRevision[] $revisions */
		$revisions = $this->entities[$key];

		if ( !is_int( $revisionId ) ) {
			$revisionIds = array_keys( $revisions );
			$revisionId = end( $revisionIds );
		} else if ( !isset( $revisions[$revisionId] ) ) {
			throw new StorageException( "no such revision for entity $key: $revisionId" );
		}

		$revision = $revisions[$revisionId];
		$revision = new EntityRevision( // return a copy!
			$revision->getEntity()->copy(), // return a copy!
			$revision->getRevisionId(),
			$revision->getTimestamp()
		);

		return $revision;
	}

	/**
	 * @since 0.5
	 * @see EntityRevisionBatchLookup::getEntityRevisions
	 *
	 * @param EntityId[] $entityIds
	 *
	 * @throws InvalidArgumentException
	 * @throws StorageException
	 * @return array entity id serialization -> EntityRevision|EntityRedirect|null
	 */
	public function getEntityRevisions( array $entityIds ) {
		$entityRevisions = array();

		foreach ( $entityIds as $entityId ) {
			if ( !$entityId instanceof EntityId ) {
				throw new InvalidArgumentException( '$entityIds needs to be an array of EntityIds' );
			}

			$key = $entityId->getSerialization();
			if ( !isset( $this->redirects[$key] ) ) {
				$entityRevisions[$key] = $this->getEntityRevision( $entityId );
			} else {
				$entityRevisions[$key] = $this->redirects[$key];
			}
		}

		return $entityRevisions;
	}

	/**
	 * See EntityLookup::hasEntity()
	 *
	 * @since 0.4
	 *
	 * @param EntityId $entityId
	 *
	 * @return bool
	 */
	public function hasEntity( EntityId $entityId ) {
		return $this->getEntity( $entityId ) !== null;
	}

	/**
	 * @see SiteLinkLookup::getConflictsForItem
	 *
	 * @param Item $item
	 * @param DatabaseBase|null $db
	 *
	 * @return array[]
	 */
	public function getConflictsForItem( Item $item, DatabaseBase $db = null ) {
		$newLinks = array();

		/** @var SiteLink $siteLink */
		foreach ( $item->getSiteLinkList() as $siteLink ) {
			$newLinks[$siteLink->getSiteId()] = $siteLink->getPageName();
		}

		$conflicts = array();

		foreach ( array_keys( $this->entities ) as $idString ) {
			$itemId = $this->parseId( $idString );

			if ( !( $itemId instanceof ItemId ) ) {
				continue;
			}

			$oldLinks = $this->getLinks( array( $itemId->getNumericId() ) );

			foreach ( $oldLinks as $link ) {
				list( $wiki, $page, $numericId ) = $link;

				if ( $item->getId() && $numericId === $item->getId()->getNumericId() ) {
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
	 * @see SiteLinkLookup::getItemIdForLink
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
	 * @param SiteLink $siteLink
	 *
	 * @return ItemId|null
	 */
	public function getEntityIdForSiteLink( SiteLink $siteLink ) {
		$globalSiteId = $siteLink->getSiteId();
		$pageName = $siteLink->getPageName();

		// @todo: fix test data to use titles with underscores, like the site link table does it
		$title = Title::newFromText( $pageName );
		$pageTitle = $title->getDBkey();

		return $this->getItemIdForLink( $globalSiteId, $pageTitle );
	}

	/**
	 * Registers the sitelinks of the given Item so they can later be found with getLinks, etc
	 *
	 * @param Item $item
	 */
	private function registerSiteLinks( Item $item ) {
		$itemId = $item->getId();
		$numericId = $itemId->getNumericId();

		$this->unregisterSiteLinks( $itemId );

		/** @var SiteLink $siteLink */
		foreach ( $item->getSiteLinkList() as $siteLink ) {
			$key = $siteLink->getSiteId() . ':' . $siteLink->getPageName();
			$this->itemByLink[$key] = $numericId;
		}
	}

	/**
	 * Unregisters the sitelinks of the given Item so they are no longer found with getLinks, etc
	 *
	 * @param ItemId $itemId
	 */
	private function unregisterSiteLinks( ItemId $itemId ) {
		$numericId = $itemId->getNumericId();

		foreach ( $this->itemByLink as $key => $n ) {
			if ( $n === $numericId ) {
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
	 * @param int $revisionId
	 * @param int|string $timestamp
	 * @param User|string|null $user
	 *
	 * @return EntityRevision
	 */
	public function putEntity( Entity $entity, $revisionId = 0, $timestamp = 0, $user = null ) {
		if ( $entity->getId() === null ) {
			$this->assignFreshId( $entity );
		}

		$oldEntity = $this->getEntity( $entity->getId() );

		if ( $oldEntity && ( $oldEntity instanceof Item ) ) {
			// clean up old sitelinks
			$this->unregisterSiteLinks( $entity->getId() );
		}

		if ( $entity instanceof Item ) {
			// add new sitelinks
			$this->registerSiteLinks( $entity );
		}

		if ( $revisionId === 0 ) {
			$revisionId = ++$this->maxRevisionId;
		}

		$this->maxEntityId = max( $this->maxEntityId, $entity->getId()->getNumericId() );
		$this->maxRevisionId = max( $this->maxRevisionId, $revisionId );

		$revision = new EntityRevision(
			$entity->copy(), // note: always clone
			$revisionId,
			wfTimestamp( TS_MW, $timestamp )
		);

		if ( $user !== null ) {
			if ( $user instanceof User ) {
				$user = $user->getName();
			}

			// just glue the user on here...
			$revision->user = $user;
		}

		$key = $entity->getId()->getSerialization();
		unset( $this->redirects[$key] );

		if ( !array_key_exists( $key, $this->entities ) ) {
			$this->entities[$key] = array();
		}
		$this->entities[$key][$revisionId] = $revision;
		ksort( $this->entities[$key] );

		return $revision;
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
	 * @param EntityId $entityId
	 *
	 * @return Entity
	 */
	public function removeEntity( EntityId $entityId ) {
		try {
			$oldEntity = $this->getEntity( $entityId );

			if ( $oldEntity && ( $oldEntity instanceof Item ) ) {
				// clean up old sitelinks
				$this->unregisterSiteLinks( $entityId );
			}
		} catch ( StorageException $ex ) {
			$oldEntity = null; // ignore
		}

		$key = $entityId->getSerialization();
		unset( $this->entities[$key] );
		unset( $this->redirects[$key] );

		return $oldEntity;
	}

	/**
	 * @see SiteLinkLookup::countLinks
	 *
	 * @param int[] $numericIds Numeric (unprefixed) item ids
	 * @param string[] $siteIds
	 * @param string[] $pageNames
	 *
	 * @return int
	 */
	public function countLinks( array $numericIds = array(), array $siteIds = array(), array $pageNames = array() ) {
		return count( $this->getLinks( $numericIds, $siteIds, $pageNames ) );
	}

	/**
	 * @see SiteLinkLookup::getLinks
	 *
	 * @param int[] $numericIds Numeric (unprefixed) item ids
	 * @param string[] $siteIds
	 * @param string[] $pageNames
	 *
	 * @return array[]
	 */
	public function getLinks( array $numericIds = array(), array $siteIds = array(), array $pageNames = array() ) {
		$links = array();

		foreach ( array_keys( $this->entities ) as $idString ) {
			$itemId = $this->parseId( $idString );

			if ( !( $itemId instanceof ItemId ) ) {
				continue;
			}

			if ( !empty( $numericIds ) && !in_array( $itemId->getNumericId(), $numericIds ) ) {
				continue;
			}

			/** @var Item $item */
			$item = $this->getEntity( $itemId );

			/** @var SiteLink $siteLink */
			foreach ( $item->getSiteLinkList() as $siteLink ) {
				if ( $this->linkMatches( $item, $siteLink, $numericIds, $siteIds, $pageNames ) ) {
					$links[] = array(
						$siteLink->getSiteId(),
						$siteLink->getPageName(),
						$item->getId()->getNumericId(),
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
	 * @param SiteLink $siteLink
	 * @param int[] $numericIds Numeric (unprefixed) item ids
	 * @param string[] $siteIds
	 * @param string[] $pageNames
	 *
	 * @return bool
	 */
	private function linkMatches(
		Item $item,
		SiteLink $siteLink,
		array $numericIds,
		array $siteIds = array(),
		array $pageNames = array()
	) {
		return ( empty( $numericIds ) || in_array( $item->getId()->getNumericId(), $numericIds ) )
			&& ( empty( $siteIds ) || in_array( $siteLink->getSiteId(), $siteIds ) )
			&& ( empty( $pageNames ) || in_array( $siteLink->getPageName(), $pageNames ) );
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
	 * @param EntityId[] $entityIds
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
	 * @param string $propertyLabel
	 * @param string $languageCode
	 *
	 * @return EntityDocument|null
	 */
	public function getPropertyByLabel( $propertyLabel, $languageCode ) {
		foreach ( array_keys( $this->entities ) as $idString ) {
			$propertyId = $this->parseId( $idString );

			if ( !( $propertyId instanceof PropertyId ) ) {
				continue;
			}

			$property = $this->getEntity( $propertyId );

			if ( !( $property instanceof FingerprintProvider ) ) {
				continue;
			}

			$labels = $property->getFingerprint()->getLabels();

			if ( $labels->hasTermForLanguage( $languageCode )
				&& $labels->getByLanguage( $languageCode )->getText() === $propertyLabel
			) {
				return $property;
			}
		}

		return null;
	}

	/**
	 * @param EntityId[] $entityIds
	 *
	 * @return GenericEntityInfoBuilder
	 */
	public function newEntityInfoBuilder( array $entityIds ) {
		return new GenericEntityInfoBuilder( $entityIds, new BasicEntityIdParser(), $this );
	}

	/**
	 * @see PropertyDataTypeLookup::getDataTypeIdForProperty
	 *
	 * @since 0.5
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return string
	 * @throws PropertyNotFoundException
	 */
	public function getDataTypeIdForProperty( PropertyId $propertyId ) {
		$entity = $this->getEntity( $propertyId );

		if ( $entity instanceof Property ) {
			return $entity->getDataTypeId();
		}

		throw new PropertyNotFoundException( $propertyId );
	}

	/**
	 * @see EntityRevisionLookup::getLatestRevisionId
	 *
	 * @param EntityId $entityId
	 * @param string $mode
	 *
	 * @return int|false
	 */
	public function getLatestRevisionId( EntityId $entityId, $mode = self::LATEST_FROM_SLAVE ) {
		$revision = $this->getEntityRevision( $entityId, $mode );

		return $revision === null ? false : $revision->getRevisionId();
	}

	/**
	 * Stores the given Entity.
	 *
	 * @param Entity $entity the entity to save.
	 * @param string $summary ignored
	 * @param User $user ignored
	 * @param int $flags EDIT_XXX flags, as defined for WikiPage::doEditContent.
	 * @param int|bool $baseRevisionId the revision ID $entity is based on. Saving should fail if
	 * $baseRevId is no longer the current revision.
	 *
	 * @see WikiPage::doEditContent
	 *
	 * @return EntityRevision
	 *
	 * @throws StorageException
	 */
	public function saveEntity( Entity $entity, $summary, User $user, $flags = 0, $baseRevisionId = false ) {
		$entityId = $entity->getId();

		$status = Status::newGood();

		if ( ( $flags & EDIT_NEW ) > 0 && $entityId && $this->hasEntity( $entityId ) ) {
			$status->fatal( 'edit-already-exists' );
		}

		if ( ( $flags & EDIT_UPDATE ) > 0 && !$this->hasEntity( $entityId ) ) {
			$status->fatal( 'edit-gone-missing' );
		}

		if ( $baseRevisionId !== false && !$this->hasEntity( $entityId ) ) {
			//TODO: find correct message key to use with status??
			throw new StorageException( 'No base revision found for ' . $entityId->getSerialization() );
		}

		if ( $baseRevisionId !== false && $this->getEntityRevision( $entityId )->getRevisionId() !== $baseRevisionId ) {
			$status->fatal( 'edit-conflict' );
		}

		if ( !$status->isOK() ) {
			throw new StorageException( $status );
		}

		$revision = $this->putEntity( $entity, 0, 0, $user );

		$this->putLog( $revision->getRevisionId(), $entity->getId(), $summary, $user->getName() );
		return $revision;
	}

	/**
	 * @see EntityStore::saveRedirect
	 *
	 * @param EntityRedirect $redirect
	 * @param string $summary
	 * @param User $user
	 * @param int $flags
	 * @param int|bool $baseRevisionId
	 *
	 * @throws StorageException If the given type of entity does not support redirects
	 * @return int The revision id created by storing the redirect
	 */
	public function saveRedirect( EntityRedirect $redirect, $summary, User $user, $flags = 0, $baseRevisionId = false ) {
		if ( !( $redirect->getEntityId() instanceof ItemId ) ) {
			throw new StorageException( 'Entity type does not support redirects: ' . $redirect->getEntityId()->getEntityType() );
		}

		$this->putRedirect( $redirect );

		$revisionId = ++$this->maxRevisionId;
		$this->putLog( $revisionId, $redirect->getEntityId(), $summary, $user->getName() );

		return $revisionId;
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
	 * @see EditPage::userWasLastToEdit
	 *
	 * @param User $user the user
	 * @param EntityId $entityId the entity to check
	 * @param int $lastRevisionId the revision to check from
	 *
	 * @return bool
	 */
	public function userWasLastToEdit( User $user, EntityId $entityId, $lastRevisionId ) {
		$key = $entityId->getSerialization();
		if ( !isset( $this->entities[$key] ) ) {
			return false;
		}

		/** @var EntityRevision $revision */
		foreach ( $this->entities[$key] as $revision ) {
			if ( $revision->getRevisionId() >= $lastRevisionId ) {
				if ( isset( $revision->user ) && $revision->user !== $user->getName() ) {
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
	 * @param EntityId $entityId the entity to watch
	 * @param bool $watch whether to watch or unwatch the page.
	 */
	public function updateWatchlist( User $user, EntityId $entityId, $watch ) {
		if ( $watch ) {
			$this->watchlist[ $user->getName() ][ $entityId->getSerialization() ] = true;
		} else {
			unset( $this->watchlist[ $user->getName() ][ $entityId->getSerialization() ] );
		}
	}

	/**
	 * Determines whether the given user is watching the given item
	 *
	 * @param User $user
	 * @param EntityId $entityId the entity to watch
	 *
	 * @return bool
	 */
	public function isWatching( User $user, EntityId $entityId ) {
		return isset( $this->watchlist[ $user->getName() ][ $entityId->getSerialization() ] );
	}

	/**
	 * @see EntityStore::assignFreshId
	 *
	 * @param Entity $entity
	 *
	 * @throws StorageException
	 */
	public function assignFreshId( Entity $entity ) {
		//TODO: Find a canonical way to generate an EntityId from the maxId number.
		//XXX: Using setId() with an integer argument is deprecated!
		$numericId = ++$this->maxEntityId;
		$entity->setId( $numericId );
	}

	/**
	 * @param string $idString
	 *
	 * @return ItemId|PropertyId
	 */
	private function parseId( $idString ) {
		$parser = new BasicEntityIdParser();
		return $parser->parse( $idString );
	}

	/**
	 * @param int $revisionId
	 * @param EntityId|string $entityId
	 * @param string $summary
	 * @param User|string $user
	 */
	private function putLog( $revisionId, $entityId, $summary, $user ) {
		if ( $entityId instanceof EntityId ) {
			$entityId = $entityId->getSerialization();
		}

		if ( $user instanceof User ) {
			$user = $user->getName();
		}

		$this->log[$revisionId] = array(
			'revision' => intval( $revisionId ),
			'entity' => $entityId,
			'summary' => $summary,
			'user' => $user,
		);
	}

	/**
	 * Returns the log entry for the given revision Id.
	 *
	 * @param int $revisionId
	 *
	 * @return array|null An associative array containing the fields
	 * 'revision', 'entity', 'summary', and 'user'.
	 */
	public function getLogEntry( $revisionId ) {
		return array_key_exists( $revisionId, $this->log ) ? $this->log[$revisionId] : null;
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

}

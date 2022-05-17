<?php

namespace Wikibase\Lib\Tests;

use InvalidArgumentException;
use MediaWiki\User\UserIdentity;
use Status;
use User;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Int32EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityRedirectLookup;
use Wikibase\DataModel\Services\Lookup\EntityRedirectLookupException;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\HashSiteLinkStore;
use Wikibase\Lib\Store\LatestRevisionIdResult;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\RedirectRevision;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Lib\Store\SiteLinkStore;
use Wikibase\Lib\Store\StorageException;

/**
 * @deprecated Try to use a simpler fake. The complexity and coupling of this
 * test double are very high, so it is good to avoid binding to it.
 *
 * Mock repository for use in tests.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class MockRepository implements
	EntityLookup,
	EntityRedirectLookup,
	EntityRevisionLookup,
	EntityStore,
	PropertyDataTypeLookup,
	SiteLinkLookup
{

	/**
	 * @var SiteLinkStore
	 */
	private $siteLinkStore;

	/**
	 * Entity id serialization => array of EntityRevision
	 *
	 * @var array[]
	 */
	private $entities = [];

	/**
	 * Log entries. Each entry has the following fields:
	 * revision, entity, summary, user, tags
	 *
	 * @var array[]
	 */
	private $log = [];

	/**
	 * Entity id serialization => EntityRedirect
	 *
	 * @var RedirectRevision[]
	 */
	private $redirects = [];

	/**
	 * User ID + Entity Id -> bool
	 *
	 * @var bool[]
	 */
	private $watchlist = [];

	/**
	 * @var int
	 */
	private $maxEntityId = 0;

	/**
	 * @var int
	 */
	private $maxRevisionId = 0;

	public function __construct() {
		$this->siteLinkStore = new HashSiteLinkStore();
	}

	/**
	 * @see EntityLookup::getEntity
	 *
	 * @param EntityId $entityId
	 *
	 * @return EntityDocument|null
	 * @throws StorageException
	 */
	public function getEntity( EntityId $entityId ) {
		$revision = $this->getEntityRevision( $entityId );

		return $revision === null ? null : $revision->getEntity()->copy();
	}

	/**
	 * @see EntityRevisionLookup::getEntityRevision
	 *
	 * @param EntityId $entityId
	 * @param int $revisionId The desired revision id, or 0 for the latest revision.
	 * @param string $mode LATEST_FROM_REPLICA, LATEST_FROM_REPLICA_WITH_FALLBACK or
	 *        LATEST_FROM_MASTER.
	 *
	 * @throws RevisionedUnresolvedRedirectException
	 * @throws StorageException
	 * @return EntityRevision|null
	 */
	public function getEntityRevision(
		EntityId $entityId,
		$revisionId = 0,
		$mode = LookupConstants::LATEST_FROM_REPLICA
	) {
		$key = $entityId->getSerialization();

		if ( isset( $this->redirects[$key] ) ) {
			$redirRev = $this->redirects[$key];
			throw new RevisionedUnresolvedRedirectException(
				$entityId,
				$redirRev->getRedirect()->getTargetId(),
				$redirRev->getRevisionId(),
				$redirRev->getTimestamp()
			);
		}

		if ( empty( $this->entities[$key] ) ) {
			return null;
		}

		if ( !is_int( $revisionId ) ) {
			wfWarn( 'getEntityRevision() called with $revisionId = false or a string, use 0 instead.' );
			$revisionId = 0;
		}

		/** @var EntityRevision[] $revisions */
		$revisions = $this->entities[$key];

		if ( $revisionId === 0 ) {
			$revisionIds = array_keys( $revisions );
			$revisionId = end( $revisionIds );
		} elseif ( !isset( $revisions[$revisionId] ) ) {
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
	 * See EntityLookup::hasEntity()
	 *
	 * @param EntityId $entityId
	 *
	 * @return bool
	 */
	public function hasEntity( EntityId $entityId ) {
		return $this->getEntity( $entityId ) !== null;
	}

	public function getItemIdForLink( string $globalSiteId, string $pageTitle ): ?ItemId {
		return $this->siteLinkStore->getItemIdForLink( $globalSiteId, $pageTitle );
	}

	public function getItemIdForSiteLink( SiteLink $siteLink ): ?ItemId {
		return $this->siteLinkStore->getItemIdForSiteLink( $siteLink );
	}

	/**
	 * Registers the sitelinks of the given Item so they can later be found with getLinks, etc
	 *
	 * @param Item $item
	 */
	private function registerSiteLinks( Item $item ) {
		$this->siteLinkStore->saveLinksOfItem( $item );
	}

	/**
	 * Unregisters the sitelinks of the given Item so they are no longer found with getLinks, etc
	 *
	 * @param ItemId $itemId
	 */
	private function unregisterSiteLinks( ItemId $itemId ) {
		$this->siteLinkStore->deleteLinksOfItem( $itemId );
	}

	/**
	 * Puts an entity into the mock repository. If there already is an entity with the same ID
	 * in the mock repository, it is not removed, but replaced as the current one. If a revision
	 * ID is given, the entity with the highest revision ID is considered the current one.
	 *
	 * @param EntityDocument $entity
	 * @param int $revisionId
	 * @param int|string $timestamp
	 * @param User|string|null $user
	 *
	 * @throws StorageException
	 * @return EntityRevision
	 */
	public function putEntity( EntityDocument $entity, $revisionId = 0, $timestamp = 0, $user = null ) {
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

		$this->updateMaxNumericId( $entity->getId() );
		$this->maxRevisionId = max( $this->maxRevisionId, $revisionId );

		$revision = new EntityRevision(
			$entity->copy(), // note: always clone
			$revisionId,
			wfTimestamp( TS_MW, $timestamp )
		);

		if ( $user !== null ) {
			if ( $user instanceof UserIdentity ) {
				$user = $user->getName();
			}

			// just glue the user on here...
			$revision->user = $user;
		}

		$key = $entity->getId()->getSerialization();
		unset( $this->redirects[$key] );

		if ( !array_key_exists( $key, $this->entities ) ) {
			$this->entities[$key] = [];
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
	 * @param int $revisionId
	 * @param string|int $timestamp
	 *
	 * @throws StorageException
	 */
	public function putRedirect( EntityRedirect $redirect, $revisionId = 0, $timestamp = 0 ) {
		$key = $redirect->getEntityId()->getSerialization();

		if ( isset( $this->entities[$key] ) ) {
			$this->removeEntity( $redirect->getEntityId() );
		}

		if ( $revisionId === 0 ) {
			$revisionId = ++$this->maxRevisionId;
		}

		$this->updateMaxNumericId( $redirect->getTargetId() );
		$this->maxRevisionId = max( $this->maxRevisionId, $revisionId );

		$this->redirects[$key] = new RedirectRevision(
			$redirect, // EntityRedirect is immutable
			$revisionId,
			wfTimestamp( TS_MW, $timestamp )
		);
	}

	/**
	 * Removes an entity from the mock repository.
	 *
	 * @param EntityId $entityId
	 *
	 * @return EntityDocument
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

	public function getLinks(
		?array $numericIds = null,
		?array $siteIds = null,
		?array $pageNames = null
	): array {
		return $this->siteLinkStore->getLinks( $numericIds, $siteIds, $pageNames );
	}

	/**
	 * Fetches the entities with provided ids and returns them.
	 * The result array contains the prefixed entity ids as keys.
	 * The values are either an EntityDocument or null, if there is no entity with the associated id.
	 *
	 * The revisions can be specified as an array holding an integer element for each
	 * id in the $entityIds array or false for latest. If all should be latest, false
	 * can be provided instead of an array.
	 *
	 * @param EntityId[] $entityIds
	 *
	 * @return EntityDocument[]|null[]
	 */
	public function getEntities( array $entityIds ) {
		$entities = [];

		foreach ( $entityIds as $entityId ) {
			if ( is_string( $entityId ) ) {
				$entityId = $this->parseId( $entityId );
			}

			$entities[$entityId->getSerialization()] = $this->getEntity( $entityId );
		}

		return $entities;
	}

	public function getSiteLinksForItem( ItemId $itemId ): array {
		return $this->siteLinkStore->getSiteLinksForItem( $itemId );
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

			if ( !( $property instanceof LabelsProvider ) ) {
				continue;
			}

			$labels = $property->getLabels();

			if ( $labels->hasTermForLanguage( $languageCode )
				&& $labels->getByLanguage( $languageCode )->getText() === $propertyLabel
			) {
				return $property;
			}
		}

		return null;
	}

	/**
	 * @see PropertyDataTypeLookup::getDataTypeIdForProperty
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return string
	 * @throws PropertyDataTypeLookupException
	 */
	public function getDataTypeIdForProperty( PropertyId $propertyId ) {
		$entity = $this->getEntity( $propertyId );

		if ( $entity instanceof Property ) {
			return $entity->getDataTypeId();
		}

		throw new PropertyDataTypeLookupException( $propertyId );
	}

	/**
	 * @see EntityRevisionLookup::getLatestRevisionId
	 *
	 * @param EntityId $entityId
	 * @param string $mode
	 *
	 * @return LatestRevisionIdResult
	 */
	public function getLatestRevisionId( EntityId $entityId, $mode = LookupConstants::LATEST_FROM_REPLICA ) {
		try {
			$revision = $this->getEntityRevision( $entityId, 0, $mode );
		} catch ( RevisionedUnresolvedRedirectException $e ) {
			return LatestRevisionIdResult::redirect( $e->getRevisionId(), $e->getRedirectTargetId() );
		}

		return $revision === null
			? LatestRevisionIdResult::nonexistentEntity()
			: LatestRevisionIdResult::concreteRevision( $revision->getRevisionId(), $revision->getTimestamp() );
	}

	/**
	 * Stores the given Entity.
	 *
	 * @param EntityDocument $entity the entity to save.
	 * @param string $summary ignored
	 * @param User $user ignored
	 * @param int $flags EDIT_XXX flags, as defined for WikiPage::doEditContent.
	 * @param int|bool $baseRevisionId the revision ID $entity is based on. Saving should fail if
	 * $baseRevId is no longer the current revision.
	 * @param string[] $tags added to log entry
	 *
	 * @see WikiPage::doEditContent
	 *
	 * @return EntityRevision
	 * @throws StorageException
	 */
	public function saveEntity( EntityDocument $entity, $summary, User $user, $flags = 0, $baseRevisionId = false, array $tags = [] ) {
		$entityId = $entity->getId();

		$status = Status::newGood();

		if ( ( $flags & EDIT_NEW ) && $entityId && $this->hasEntity( $entityId ) ) {
			$status->fatal( 'edit-already-exists' );
		}

		if ( ( $flags & EDIT_UPDATE ) && !$this->hasEntity( $entityId ) ) {
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

		$this->putLog( $revision->getRevisionId(), $entity->getId(), $summary, $user->getName(), $tags );
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
	 * @param string[] $tags
	 *
	 * @throws StorageException If the given type of entity does not support redirects
	 * @return int The revision id created by storing the redirect
	 */
	public function saveRedirect( EntityRedirect $redirect, $summary, User $user, $flags = 0, $baseRevisionId = false, array $tags = [] ) {
		if ( !( $redirect->getEntityId() instanceof ItemId ) ) {
			throw new StorageException( 'Entity type does not support redirects: ' . $redirect->getEntityId()->getEntityType() );
		}

		$this->putRedirect( $redirect );

		$revisionId = ++$this->maxRevisionId;
		$this->putLog( $revisionId, $redirect->getEntityId(), $summary, $user->getName(), $tags );

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
	 * @param User $user
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
	 * @param EntityId $id
	 *
	 * @throws StorageException
	 */
	private function updateMaxNumericId( EntityId $id ) {
		if ( !( $id instanceof Int32EntityId ) ) {
			return;
		}

		$this->maxEntityId = max( $this->maxEntityId, $id->getNumericId() );
	}

	/**
	 * @see EntityStore::assignFreshId
	 *
	 * @param EntityDocument $entity
	 *
	 * @throws InvalidArgumentException when the entity type does not support setting numeric ids.
	 */
	public function assignFreshId( EntityDocument $entity ) {
		//TODO: Find a canonical way to generate an EntityId from the maxId number.
		$numericId = ++$this->maxEntityId;

		if ( $entity instanceof Item ) {
			$entity->setId( ItemId::newFromNumber( $numericId ) );
			return;
		}

		if ( $entity instanceof Property ) {
			$entity->setId( NumericPropertyId::newFromNumber( $numericId ) );
			return;
		}

		throw new \RuntimeException( 'Cannot create a new ID for non-items and non-properties' );
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
	 * @param string[] $tags
	 */
	private function putLog( $revisionId, $entityId, $summary, $user, array $tags = [] ) {
		if ( $entityId instanceof EntityId ) {
			$entityId = $entityId->getSerialization();
		}

		if ( $user instanceof UserIdentity ) {
			$user = $user->getName();
		}

		$this->log[$revisionId] = [
			'revision' => $revisionId,
			'entity' => $entityId,
			'summary' => $summary,
			'user' => $user,
			'tags' => $tags,
		];
	}

	/**
	 * Returns the log entry for the given revision Id.
	 *
	 * @param int $revisionId
	 *
	 * @return array|null An associative array containing the fields
	 * 'revision', 'entity', 'summary', 'user', and 'tags'.
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
	 * 'revision', 'entity', 'summary', 'user', and 'tags'.
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
	 * Returns the IDs that redirect to (are aliases of) the given target entity.
	 *
	 * @param EntityId $targetId
	 *
	 * @return EntityId[]
	 */
	public function getRedirectIds( EntityId $targetId ) {
		$redirects = [];

		foreach ( $this->redirects as $redirRev ) {
			$redir = $redirRev->getRedirect();
			if ( $redir->getTargetId()->equals( $targetId ) ) {
				$redirects[] = $redir->getEntityId();
			}
		}

		return $redirects;
	}

	/**
	 * Returns the redirect target associated with the given redirect ID.
	 *
	 * @param EntityId $entityId
	 * @param string $forUpdate
	 *
	 * @return EntityId|null The ID of the redirect target, or null if $entityId
	 *         does not refer to a redirect
	 * @throws EntityRedirectLookupException
	 */
	public function getRedirectForEntityId( EntityId $entityId, $forUpdate = '' ) {
		$key = $entityId->getSerialization();

		if ( isset( $this->redirects[$key] ) ) {
			return $this->redirects[$key]->getRedirect()->getTargetId();
		}

		if ( isset( $this->entities[$key] ) ) {
			return null;
		}

		throw new EntityRedirectLookupException( $entityId );
	}

	/**
	 * @see EntityStore::canCreateWithCustomId
	 *
	 * @param EntityId $id
	 *
	 * @return bool
	 */
	public function canCreateWithCustomId( EntityId $id ) {
		return false;
	}

}

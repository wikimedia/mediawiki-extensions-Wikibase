<?php

namespace Wikibase\Repo\Store\Sql;

use CommentStoreComment;
use InvalidArgumentException;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\RevisionStore;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\User\ActorNormalization;
use MediaWiki\Watchlist\WatchlistManager;
use MWException;
use RecentChange;
use Status;
use Title;
use User;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Content\EntityContent;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\GenericEventDispatcher;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Store\IdGenerator;
use Wikimedia\Rdbms\SelectQueryBuilder;
use WikiPage;

/**
 * EntityStore implementation based on WikiPage.
 *
 * For more information on the relationship between entities and wiki pages, see
 * docs/entity-storage.wiki.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class WikiPageEntityStore implements EntityStore {

	/**
	 * @var EntityContentFactory
	 */
	private $contentFactory;

	/**
	 * @var EntityTitleStoreLookup
	 */
	private $entityTitleStoreLookup;

	/**
	 * @var IdGenerator
	 */
	private $idGenerator;

	/**
	 * @var GenericEventDispatcher
	 */
	private $dispatcher;

	/**
	 * @var EntityIdComposer
	 */
	private $entityIdComposer;

	/**
	 * @var RevisionStore
	 */
	private $revisionStore;

	/** @var DatabaseEntitySource */
	private $entitySource;

	private ActorNormalization $actorNormalization;

	/**
	 * @var PermissionManager
	 */
	private $permissionManager;

	/**
	 * @var WatchlistManager
	 */
	private $watchlistManager;

	/** @var WikiPageFactory */
	private $wikiPageFactory;

	/**
	 * @var RepoDomainDb
	 */
	private $db;

	/**
	 * @param EntityContentFactory $contentFactory
	 * @param EntityTitleStoreLookup $entityTitleStoreLookup
	 * @param IdGenerator $idGenerator
	 * @param EntityIdComposer $entityIdComposer
	 * @param RevisionStore $revisionStore A RevisionStore for the local database.
	 * @param DatabaseEntitySource $entitySource
	 * @param PermissionManager $permissionManager
	 * @param WatchlistManager $watchlistManager
	 * @param WikiPageFactory $wikiPageFactory
	 * @param RepoDomainDb $repoDomainDb
	 */
	public function __construct(
		EntityContentFactory $contentFactory,
		EntityTitleStoreLookup $entityTitleStoreLookup,
		IdGenerator $idGenerator,
		EntityIdComposer $entityIdComposer,
		RevisionStore $revisionStore,
		DatabaseEntitySource $entitySource,
		ActorNormalization $actorNormalization,
		PermissionManager $permissionManager,
		WatchlistManager $watchlistManager,
		WikiPageFactory $wikiPageFactory,
		RepoDomainDb $repoDomainDb
	) {
		$this->contentFactory = $contentFactory;
		$this->entityTitleStoreLookup = $entityTitleStoreLookup;
		$this->idGenerator = $idGenerator;

		$this->dispatcher = new GenericEventDispatcher( EntityStoreWatcher::class );

		$this->entityIdComposer = $entityIdComposer;
		$this->revisionStore = $revisionStore;

		$this->entitySource = $entitySource;

		$this->actorNormalization = $actorNormalization;
		$this->permissionManager = $permissionManager;

		$this->watchlistManager = $watchlistManager;
		$this->wikiPageFactory = $wikiPageFactory;
		$this->db = $repoDomainDb;
	}

	private function assertCanStoreEntity( EntityId $id ) {
		$this->assertEntityIdFromKnownSource( $id );
	}

	private function assertEntityIdFromKnownSource( EntityId $id ) {
		if ( !$this->entityIdFromKnownSource( $id ) ) {
			throw new InvalidArgumentException(
				'Entities of type: ' . $id->getEntityType() . ' is not provided by source: ' . $this->entitySource->getSourceName()
			);
		}
	}

	/**
	 * @see EntityStore::assignFreshId()
	 *
	 * @param EntityDocument $entity
	 *
	 * @throws StorageException
	 * @throws InvalidArgumentException
	 */
	public function assignFreshId( EntityDocument $entity ) {
		if ( $entity->getId() !== null ) {
			throw new InvalidArgumentException( 'This entity already has an ID: ' . $entity->getId() . '!' );
		}

		$type = $entity->getType();
		$handler = $this->contentFactory->getContentHandlerForType( $type );

		if ( !$handler->allowAutomaticIds() ) {
			throw new StorageException( $type . ' entities do not support automatic IDs!' );
		}

		// TODO: move this into EntityHandler!
		$contentModelId = $handler->getModelID();
		$numericId = $this->idGenerator->getNewId( $contentModelId );

		$entityId = $this->entityIdComposer->composeEntityId( '', $type, $numericId );
		$entity->setId( $entityId );
	}

	/**
	 * @see EntityStore::canCreateWithCustomId
	 *
	 * @param EntityId $id
	 *
	 * @throws StorageException
	 * @return bool
	 */
	public function canCreateWithCustomId( EntityId $id ) {
		if ( !$this->entityIdFromKnownSource( $id ) ) {
			return false;
		}

		$type = $id->getEntityType();
		$handler = $this->contentFactory->getContentHandlerForType( $type );

		return $handler->canCreateWithCustomId( $id );
	}

	private function entityIdFromKnownSource( EntityId $id ) {
		return in_array( $id->getEntityType(), $this->entitySource->getEntityTypes() );
	}

	/**
	 * Registers a watcher that will be notified whenever an entity is
	 * updated or deleted.
	 *
	 * @param EntityStoreWatcher $watcher
	 */
	public function registerWatcher( EntityStoreWatcher $watcher ) {
		$this->dispatcher->registerWatcher( $watcher );
	}

	/**
	 * Returns the WikiPage object for the item with provided entity.
	 *
	 * @param EntityId $entityId
	 *
	 * @throws InvalidArgumentException
	 * @throws StorageException
	 * @return WikiPage
	 */
	public function getWikiPageForEntity( EntityId $entityId ) {
		$this->assertCanStoreEntity( $entityId );

		$title = $this->getTitleForEntity( $entityId );
		if ( !$title ) {
			throw new StorageException( 'Entity could not be mapped to a page title!' );
		}

		return $this->wikiPageFactory->newFromTitle( $title );
	}

	/**
	 * @see EntityStore::saveEntity
	 * @see WikiPage::doEditContent
	 *
	 * @param EntityDocument $entity
	 * @param string $summary
	 * @param User $user
	 * @param int $flags
	 * @param int|bool $baseRevId
	 * @param string[] $tags
	 *
	 * @throws InvalidArgumentException
	 * @throws StorageException
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
		if ( $entity->getId() === null ) {
			if ( !( $flags & EDIT_NEW ) ) {
				throw new StorageException( Status::newFatal( 'edit-gone-missing' ) );
			}

			$this->assignFreshId( $entity );
		}

		$this->assertCanStoreEntity( $entity->getId() );

		$content = $this->contentFactory->newFromEntity( $entity );
		if ( !$content->isValid() ) {
			throw new StorageException( Status::newFatal( 'invalid-content-data' ) );
		}
		$revision = $this->saveEntityContent( $content, $user, $summary, $flags, $baseRevId, $tags );

		$entityRevision = new EntityRevision(
			$entity,
			$revision->getId(),
			$revision->getTimestamp()
		);

		$this->dispatcher->dispatch( 'entityUpdated', $entityRevision );

		return $entityRevision;
	}

	/**
	 * @see EntityStore::saveRedirect
	 * @see WikiPage::doEditContent
	 *
	 * @param EntityRedirect $redirect
	 * @param string $summary
	 * @param User $user
	 * @param int $flags
	 * @param int|bool $baseRevId
	 * @param string[] $tags
	 *
	 * @throws InvalidArgumentException
	 * @throws StorageException
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
		$this->assertCanStoreEntity( $redirect->getEntityId() );
		$this->assertCanStoreEntity( $redirect->getTargetId() );

		$content = $this->contentFactory->newFromRedirect( $redirect );
		if ( !$content ) {
			throw new StorageException( 'Failed to create redirect' .
				' from ' . $redirect->getEntityId()->getSerialization() .
				' to ' . $redirect->getTargetId()->getSerialization() );
		}

		$revision = $this->saveEntityContent( $content, $user, $summary, $flags, $baseRevId, $tags );

		$this->dispatcher->dispatch( 'redirectUpdated', $redirect, $revision->getId() );

		return $revision->getId();
	}

	/**
	 * Saves the entity. If the corresponding page does not exist yet, it will be created
	 * (ie a new ID will be determined and a new page in the data NS created).
	 *
	 * @note this method should not be overloaded, and should not be extended to save additional
	 *        information to the database. Such things should be done in a way that will also be
	 *        triggered when the save is performed by calling WikiPage::doEditContent.
	 *
	 * @see WikiPage::doEditContent
	 *
	 * @param EntityContent $entityContent the entity to save.
	 * @param User $user
	 * @param string $summary
	 * @param int $flags Flags as used by WikiPage::doEditContent, use EDIT_XXX constants.
	 * @param int|bool $baseRevId
	 * @param string[] $tags
	 *
	 * @throws StorageException
	 * @return RevisionRecord The new revision (or the latest one, in case of a null edit).
	 */
	private function saveEntityContent(
		EntityContent $entityContent,
		User $user,
		$summary = '',
		$flags = 0,
		$baseRevId = false,
		array $tags = []
	) {
		global $wgUseNPPatrol, $wgUseRCPatrol;

		$id = $entityContent->getEntityId();

		$page = $this->getWikiPageForEntity( $id );
		$slotRole = $this->contentFactory->getSlotRoleForType( $id->getEntityType() );

		$updater = $page->newPageUpdater( $user );
		$updater->addTags( $tags );

		$flags = $this->adjustFlagsForMCR(
			$flags,
			$updater->grabParentRevision(),
			$slotRole
		);

		if ( $baseRevId && $updater->hasEditConflict( $baseRevId ) ) {
			throw new StorageException( Status::newFatal( 'edit-conflict' ) );
		}

		if (
			( $flags & EDIT_NEW ) === 0 &&
			$page->getRevisionRecord() &&
			$page->getRevisionRecord()->hasSlot( $slotRole ) &&
			$entityContent->equals( $page->getRevisionRecord()->getContent( $slotRole ) )
		) {
			// The size and the sha1 of entity content revisions is not always stable given they
			// depend on PHP serialization (size) and JSON serialization (sha1). These differences
			// will make MediaWiki not detect the null-edit.
			// Generally content equivalence is not strong enough for MediaWiki, but for us it should
			// be sufficent.
			return $page->getRevisionRecord();
		}

		/**
		 * @note Make sure we start saving from a clean slate. Calling WikiPage::clearPreparedEdit
		 * may cause the old content to be loaded from the database again. This may be necessary,
		 * because EntityContent is mutable, so the cached object might have changed.
		 *
		 * @todo Might be able to further optimize handling of prepared edit in WikiPage.
		 * @todo now we use PageUpdater do we still need the 2 clear calls below?
		 */
		$page->clear();
		$page->clearPreparedEdit();

		$updater->setContent( $slotRole, $entityContent );
		$needsPatrol = $wgUseRCPatrol || ( $wgUseNPPatrol && !$page->exists() );

		// TODO: this logic should not be in the storage layer, it's here for compatibility
		// with 1.31 behavior. Applying the 'autopatrol' right should be done in the same
		// place the 'bot' right is handled and passed down, perhaps via the $flags parameter.
		// Relevant callers are EditEntity, PropertyDataTypeChanger, and ItemMergeInteractor.
		if ( $needsPatrol && $this->permissionManager
				->userCan( 'autopatrol', $user, $page->getTitle() )
		) {
			$updater->setRcPatrolStatus( RecentChange::PRC_AUTOPATROLLED );
		}

		$revisionRecord = $updater->saveRevision(
			CommentStoreComment::newUnsavedComment( $summary ),
			$flags | EDIT_AUTOSUMMARY
		);

		$status = $updater->getStatus();

		if ( !$status->isOK() ) {
			throw new StorageException( $status );
		}

		// If we saved a new revision then return the record
		if ( $revisionRecord !== null ) {
			return $revisionRecord;
		} else {
			// NOTE: No new revision was created (content didn't change). Report the old one.
			// There *might* be a race condition here, but since $page already loaded the
			// latest revision, it should still be cached, and should always be the correct one.
			return $page->getRevisionRecord();
		}
	}

	/**
	 * @param int $flags
	 * @param RevisionRecord|null $parentRevision
	 * @param string $slotRole
	 * @return int
	 * @throws StorageException
	 */
	private function adjustFlagsForMCR( $flags, $parentRevision, $slotRole ) {
		if ( $flags & EDIT_UPDATE ) {
			if ( !$parentRevision ) {
				throw new StorageException( 'Can\'t perform an update with no parent revision' );
			}
			if ( !$parentRevision->hasSlot( $slotRole ) ) {
				throw new StorageException(
					'Can\'t perform an update when the parent revision doesn\'t have expected slot: ' . $slotRole
				);
			}
		}

		/**
		 * If the flags indicate a new edit, and the page already exists and we are interacting
		 * with a slot other than the main slot, adjust the slots for the MCR save.
		 * If we are interacting with the main slot, keep the NEW flag.
		 * This is consistent with previous behaviour.
		 */
		if ( $flags & EDIT_NEW && $parentRevision && $slotRole !== SlotRecord::MAIN ) {
			if ( $parentRevision->hasSlot( $slotRole ) ) {
				throw new StorageException( 'Can\'t create slot, it already exists: ' . $slotRole );
			}

			// We are creating the entity, but updating the page.
			// Unset the NEW bit, set the UPDATE bit.
			$flags = ( $flags & ~EDIT_NEW ) | EDIT_UPDATE;
		}

		return $flags;
	}

	/**
	 * @see EntityTitleStoreLookup::getTitleForId
	 *
	 * @param EntityId $entityId
	 *
	 * @return Title|null
	 */
	private function getTitleForEntity( EntityId $entityId ) {
		$title = $this->entityTitleStoreLookup->getTitleForId( $entityId );
		return $title;
	}

	/**
	 * Deletes the given entity in some underlying storage mechanism.
	 *
	 * @param EntityId $entityId
	 * @param string $reason the reason for deletion
	 * @param User $user
	 *
	 * @throws InvalidArgumentException
	 * @throws StorageException
	 */
	public function deleteEntity( EntityId $entityId, $reason, User $user ) {
		$this->assertCanStoreEntity( $entityId );
		$page = $this->getWikiPageForEntity( $entityId );
		$error = '';
		$status = $page->doDeleteArticleReal( $reason, $user, false, null, $error );

		if ( !$status->isOk() ) {
			throw new StorageException(
				'Failed to delete ' . $entityId->getSerialization() . ': ' . $error
			);
		}

		$this->dispatcher->dispatch( 'entityDeleted', $entityId );
	}

	/**
	 * Check if no edits were made by other users since the given revision.
	 * This makes the assumption that revision ids are monotonically increasing.
	 *
	 * @see EditPage::userWasLastToEdit()
	 *
	 * @param User $user
	 * @param EntityId $id the entity to check (ignored by this implementation)
	 * @param int $lastRevId the revision the user supplied
	 *
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	public function userWasLastToEdit( User $user, EntityId $id, $lastRevId ) {
		$this->assertCanStoreEntity( $id );
		$revision = $this->revisionStore->getRevisionById( $lastRevId );
		if ( !$revision ) {
			return false;
		}

		// Scan through the revision table
		$dbw = $this->db->connections()->getWriteConnection();
		$queryBuilder = $dbw->newSelectQueryBuilder()
			->select( '1' )
			->from( 'revision' )
			->where( [
				'rev_page' => $revision->getPageId(),
				'rev_id > ' . (int)$lastRevId
				. ' OR rev_timestamp > ' . $dbw->addQuotes( $dbw->timestamp( $revision->getTimestamp() ) ),
			] );
		$actorId = $this->actorNormalization->findActorId( $user, $dbw );
		if ( $actorId !== null ) {
			// @phan-suppress-next-line PhanRedundantCondition in case findActorId() changes return type
			$queryBuilder->andWhere( 'rev_actor != ' . (int)$actorId );
		}
		$res = $queryBuilder
			->orderBy( 'rev_timestamp', SelectQueryBuilder::SORT_ASC )
			->limit( 1 )
			->caller( __METHOD__ )->fetchResultSet();

		return $res->current() === false; // return true if query had no match
	}

	/**
	 * Watches or unwatches the entity.
	 *
	 * @param User $user
	 * @param EntityId $id the entity to watch
	 * @param bool $watch whether to watch or unwatch the page.
	 *
	 * @throws InvalidArgumentException
	 * @throws MWException
	 *
	 * @note keep in sync with logic in EditPage
	 */
	public function updateWatchlist( User $user, EntityId $id, $watch ) {
		$this->assertCanStoreEntity( $id );

		$title = $this->getTitleForEntity( $id );

		if (
			$user->isRegistered() &&
			$title &&
			( $watch != $this->watchlistManager->isWatchedIgnoringRights( $user, $title ) )
		) {
			if ( $watch ) {
				// Allow adding to watchlist even if user('s session) lacks 'editmywatchlist'
				// (e.g. due to bot password or OAuth grants)
				$this->watchlistManager->addWatchIgnoringRights( $user, $title );
			} else {
				$this->watchlistManager->removeWatch( $user, $title );
			}
		}
	}

	/**
	 * Determines whether the given user is watching the given item
	 *
	 * @todo move this to a separate service
	 *
	 * @param User $user
	 * @param EntityId $id the entity to watch
	 *
	 * @throws InvalidArgumentException for foreign EntityIds as watching foreign entities is not yet supported
	 * @return bool
	 */
	public function isWatching( User $user, EntityId $id ) {
		$this->assertCanStoreEntity( $id );

		$title = $this->getTitleForEntity( $id );
		return ( $title && $this->watchlistManager->isWatchedIgnoringRights( $user, $title ) );
	}

}

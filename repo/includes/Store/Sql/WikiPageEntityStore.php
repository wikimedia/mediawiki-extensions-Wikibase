<?php

namespace Wikibase\Repo\Store;

use ActorMigration;
use CommentStoreComment;
use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use MediaWiki\Storage\RevisionRecord;
use MWException;
use RecentChange;
use Status;
use Title;
use User;
use WatchAction;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\EntityContent;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\IdGenerator;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\GenericEventDispatcher;
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

	public function __construct(
		EntityContentFactory $contentFactory,
		IdGenerator $idGenerator,
		EntityIdComposer $entityIdComposer
	) {
		$this->contentFactory = $contentFactory;
		$this->idGenerator = $idGenerator;

		$this->dispatcher = new GenericEventDispatcher( EntityStoreWatcher::class );

		$this->entityIdComposer = $entityIdComposer;
	}

	/**
	 * @param EntityId $id
	 *
	 * @throws InvalidArgumentException
	 */
	private function assertLocalEntityId( EntityId $id ) {
		if ( $id->isForeign() ) {
			throw new InvalidArgumentException( 'The entity must not be foreign' );
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
		if ( $id->isForeign() ) {
			return false;
		}

		$type = $id->getEntityType();
		$handler = $this->contentFactory->getContentHandlerForType( $type );

		return $handler->canCreateWithCustomId( $id );
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
		$this->assertLocalEntityId( $entityId );

		$title = $this->getTitleForEntity( $entityId );
		if ( !$title ) {
			throw new StorageException( 'Entity could not be mapped to a page title!' );
		}

		return new WikiPage( $title );
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
		$baseRevId = false
	) {
		if ( $entity->getId() === null ) {
			if ( !( $flags & EDIT_NEW ) ) {
				throw new StorageException( Status::newFatal( 'edit-gone-missing' ) );
			}

			$this->assignFreshId( $entity );
		}

		$this->assertLocalEntityId( $entity->getId() );

		$content = $this->contentFactory->newFromEntity( $entity );
		$revision = $this->saveEntityContent( $content, $summary, $user, $flags, $baseRevId );

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
		$baseRevId = false
	) {
		$this->assertLocalEntityId( $redirect->getEntityId() );
		$this->assertLocalEntityId( $redirect->getTargetId() );

		$content = $this->contentFactory->newFromRedirect( $redirect );
		if ( !$content ) {
			throw new StorageException( 'Failed to create redirect' .
				' from ' . $redirect->getEntityId()->getSerialization() .
				' to ' . $redirect->getTargetId()->getSerialization() );
		}

		$revision = $this->saveEntityContent( $content, $summary, $user, $flags, $baseRevId );

		$this->dispatcher->dispatch( 'redirectUpdated', $redirect, $revision->getId() );

		return $revision->getId();
	}

	/**
	 * Saves the entity. If the corresponding page does not exist yet, it will be created
	 * (ie a new ID will be determined and a new page in the data NS created).
	 *
	 * @note: this method should not be overloaded, and should not be extended to save additional
	 *        information to the database. Such things should be done in a way that will also be
	 *        triggered when the save is performed by calling WikiPage::doEditContent.
	 *
	 * @see WikiPage::doEditContent
	 *
	 * @param EntityContent $entityContent the entity to save.
	 * @param string $summary
	 * @param null|User $user
	 * @param int $flags Flags as used by WikiPage::doEditContent, use EDIT_XXX constants.
	 * @param int|bool $originalRevId
	 *
	 * @throws StorageException
	 * @return RevisionRecord The new revision (or the latest one, in case of a null edit).
	 */
	private function saveEntityContent(
		EntityContent $entityContent,
		$summary = '',
		User $user = null,
		$flags = 0,
		$originalRevId = false
	) {
		// TODO get rid of evil globals
		global $wgUseNPPatrol, $wgUseRCPatrol;

		$page = $this->getWikiPageForEntity( $entityContent->getEntityId() );
		$updater = $page->newPageUpdater( $user );

		if ( $flags & EDIT_NEW ) {
			$title = $page->getTitle();
			if ( $title->exists() ) {
				throw new StorageException( Status::newFatal( 'edit-already-exists' ) );
			}
		}

		/**
		 * @note Make sure we start saving from a clean slate. Calling WikiPage::clearPreparedEdit
		 * may cause the old content to be loaded from the database again. This may be necessary,
		 * because EntityContent is mutable, so the cached object might have changed.
		 *
		 * @todo Might be able to further optimize handling of prepared edit in WikiPage.
		 * @todo now we use PageUpdater do we still need the calls below?
		 */
		$page->clear();
		$page->clearPreparedEdit();

		// TODO provide a way for entities to be stored in different slots
		$updater->setContent( 'main', $entityContent );
		$updater->setOriginalRevisionId( $originalRevId );
		$needsPatrol = $wgUseRCPatrol || ( $wgUseNPPatrol && !$page->exists() );

		// TODO: this logic should not be in the storage layer, it's here for compatibility
		// with 1.31 behavior. Applying the 'autopatrol' right should be done in the same
		// place the 'bot' right is handled, which is currently in EditPage::attemptSave.
		if ( $needsPatrol && $page->getTitle()->userCan( 'autopatrol', $user ) ) {
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
			return $page->getRevision()->getRevisionRecord();
		}
	}

	/**
	 * @see EntityTitleStoreLookup::getTitleForId
	 *
	 * @param EntityId $entityId
	 *
	 * @return Title|null
	 */
	private function getTitleForEntity( EntityId $entityId ) {
		$title = $this->contentFactory->getTitleForId( $entityId );
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
		$this->assertLocalEntityId( $entityId );
		$page = $this->getWikiPageForEntity( $entityId );
		$ok = $page->doDeleteArticle( $reason, false, 0, true, $error, $user );

		if ( !$ok ) {
			throw new StorageException(
				'Failed to delete ' . $entityId->getSerialization(). ': ' . $error
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
		$this->assertLocalEntityId( $id );
		// TODO inject me
		$revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionById( $lastRevId );
		if ( !$revision ) {
			return false;
		}

		// Scan through the revision table
		$dbw = wfGetDB( DB_MASTER );
		$revWhere = ActorMigration::newMigration()->getWhere( $dbw, 'rev_user', $user );
		$res = $dbw->select(
			[ 'revision' ] + $revWhere['tables'],
			1,
			[
				'rev_page' => $revision->getPageId(),
				'rev_id > ' . (int)$lastRevId
				. ' OR rev_timestamp > ' . $dbw->addQuotes( $revision->getTimestamp() ),
				'NOT( ' . $revWhere['conds'] . ' )',
			],
			__METHOD__,
			[ 'ORDER BY' => 'rev_timestamp ASC', 'LIMIT' => 1 ],
			$revWhere['joins']
		);

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
		$this->assertLocalEntityId( $id );

		$title = $this->getTitleForEntity( $id );

		if ( $user->isLoggedIn() && $title && ( $watch != $user->isWatched( $title ) ) ) {
			$fname = __METHOD__;

			// Do this in its own transaction to reduce contention...
			$dbw = wfGetDB( DB_MASTER );
			$dbw->onTransactionIdle( function() use ( $dbw, $title, $watch, $user, $fname ) {
				$dbw->startAtomic( $fname );
				if ( $watch ) {
					WatchAction::doWatch( $title, $user );
				} else {
					WatchAction::doUnwatch( $title, $user );
				}
				$dbw->endAtomic( $fname );
			} );
		}
	}

	/**
	 * Determines whether the given user is watching the given item
	 *
	 * @todo: move this to a separate service
	 *
	 * @param User $user
	 * @param EntityId $id the entity to watch
	 *
	 * @throws InvalidArgumentException for foreign EntityIds as watching foreign entities is not yet supported
	 * @return bool
	 */
	public function isWatching( User $user, EntityId $id ) {
		$this->assertLocalEntityId( $id );

		$title = $this->getTitleForEntity( $id );
		return ( $title && $user->isWatched( $title ) );
	}

}

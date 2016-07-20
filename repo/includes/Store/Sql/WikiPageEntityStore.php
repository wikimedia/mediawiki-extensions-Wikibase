<?php

namespace Wikibase\Repo\Store;

use MWException;
use Revision;
use Status;
use Title;
use User;
use WatchAction;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\EntityContent;
use Wikibase\EntityRevision;
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
 * @todo: move the actual implementation of the storage logic from EntityContent into this class.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
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
	 * @param EntityContentFactory $contentFactory
	 * @param IdGenerator $idGenerator
	 */
	public function __construct(
		EntityContentFactory $contentFactory,
		IdGenerator $idGenerator
	) {
		$this->contentFactory = $contentFactory;
		$this->idGenerator = $idGenerator;

		$this->dispatcher = new GenericEventDispatcher( EntityStoreWatcher::class );
	}

	/**
	 * @see EntityStore::assignFreshId()
	 *
	 * @param EntityDocument $entity
	 *
	 * @throws StorageException
	 */
	public function assignFreshId( EntityDocument $entity ) {
		if ( $entity->getId() !== null ) {
			throw new StorageException( 'This entity already has an ID: ' . $entity->getId() . '!' );
		}

		$type = $entity->getType();
		$handler = $this->contentFactory->getContentHandlerForType( $type );

		if ( !$handler->allowAutomaticIds() ) {
			throw new StorageException( $type . ' entities do not support automatic IDs!' );
		}

		// TODO: move this into EntityHandler!
		$contentModelId = $handler->getModelID();
		$numericId = $this->idGenerator->getNewId( $contentModelId );

		// FIXME: this relies on setId() accepting numeric IDs! Use an EntityIdComposer instead.
		$entity->setId( $numericId );
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
	 * @since 0.5
	 *
	 * @param EntityId $entityId
	 *
	 * @throws StorageException
	 * @return WikiPage
	 */
	public function getWikiPageForEntity( EntityId $entityId ) {
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
			if ( ( $flags & EDIT_NEW ) !== EDIT_NEW ) {
				throw new StorageException( Status::newFatal( 'edit-gone-missing' ) );
			}

			$this->assignFreshId( $entity );
		}

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
	 * @param int|bool $baseRevId
	 *
	 * @throws StorageException
	 * @return Revision The new revision (or the latest one, in case of a null edit).
	 */
	private function saveEntityContent(
		EntityContent $entityContent,
		$summary = '',
		User $user = null,
		$flags = 0,
		$baseRevId = false
	) {
		$page = $this->getWikiPageForEntity( $entityContent->getEntityId() );

		if ( ( $flags & EDIT_NEW ) === EDIT_NEW ) {
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
		 */

		$page->clear();
		$page->clearPreparedEdit();

		$status = $page->doEditContent(
			$entityContent,
			$summary,
			$flags | EDIT_AUTOSUMMARY,
			$baseRevId,
			$user
		);

		if ( !$status->isOK() ) {
			throw new StorageException( $status );
		}

		// As per convention defined by WikiPage, the new revision is in the status value:
		if ( isset( $status->value['revision'] ) ) {
			$revision = $status->value['revision'];
		} else {
			// NOTE: No new revision was created (content didn't change). Report the old one.
			// There *might* be a race condition here, but since $page already loaded the
			// latest revision, it should still be cached, and should always be the correct one.
			$revision = $page->getRevision();
		}

		return $revision;
	}

	/**
	 * @see EntityTitleLookup::getTitleForId
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
	 * @throws StorageException
	 */
	public function deleteEntity( EntityId $entityId, $reason, User $user ) {
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
	 * @param User $user the user
	 * @param EntityId $id the entity to check (ignored by this implementation)
	 * @param int $lastRevId the revision the user supplied
	 *
	 * @return bool
	 */
	public function userWasLastToEdit( User $user, EntityId $id, $lastRevId ) {
		$revision = Revision::newFromId( $lastRevId );
		if ( !$revision ) {
			return false;
		}

		// Scan through the revision table
		$dbw = wfGetDB( DB_MASTER );
		$res = $dbw->select( 'revision',
			'rev_user',
			array(
				'rev_page' => $revision->getPage(),
				'rev_id > ' . (int)$lastRevId
				. ' OR rev_timestamp > ' . $dbw->addQuotes( $revision->getTimestamp() ),
				'rev_user != ' . (int)$user->getId()
				. ' OR rev_user_text != ' . $dbw->addQuotes( $user->getName() ),
			),
			__METHOD__,
			array( 'ORDER BY' => 'rev_timestamp ASC', 'LIMIT' => 1 )
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
	 * @throws MWException
	 *
	 * @note keep in sync with logic in EditPage
	 */
	public function updateWatchlist( User $user, EntityId $id, $watch ) {
		$title = $this->getTitleForEntity( $id );

		if ( $user->isLoggedIn() && $title && ( $watch != $user->isWatched( $title ) ) ) {
			$fname = __METHOD__;

			// Do this in its own transaction to reduce contention...
			$dbw = wfGetDB( DB_MASTER );
			$dbw->onTransactionIdle( function() use ( $dbw, $title, $watch, $user, $fname ) {
				$dbw->begin( $fname );
				if ( $watch ) {
					WatchAction::doWatch( $title, $user );
				} else {
					WatchAction::doUnwatch( $title, $user );
				}
				$dbw->commit( $fname );
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
	 * @return bool
	 */
	public function isWatching( User $user, EntityId $id ) {
		$title = $this->getTitleForEntity( $id );
		return ( $title && $user->isWatched( $title ) );
	}

}

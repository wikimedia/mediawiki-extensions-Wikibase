<?php

namespace Wikibase\store;

use MWException;
use PermissionsError;
use Revision;
use Title;
use User;
use Wikibase\Entity;
use Wikibase\EntityContentFactory;
use Wikibase\EntityId;
use Wikibase\EntityRevision;
use Wikibase\StorageException;
use Wikibase\util\GenericEventDispatcher;

/**
 * EntityStore implementation based on WikiPage.
 *
 * @todo: move the actual implementation of the storage logic from EntityContent into this class.
 *
 * @since 0.5
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class WikiPageEntityStore implements EntityStore {

	/**
	 * @var EntityContentFactory
	 */
	protected $contentFactory;

	/**
	 * @param EntityContentFactory $contentFactory
	 */
	public function __construct( EntityContentFactory $contentFactory ) {
		$this->contentFactory = $contentFactory;
		$this->dispatcher = new GenericEventDispatcher( 'Wikibase\store\EntityStoreWatcher' );
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
	 * Saves the given Entity to a wiki page via a WikiPage object.
	 *
	 * @param Entity $entity the entity to save.
	 * @param string $summary the edit summary for the new revision.
	 * @param User $user the user to whom to attribute the edit
	 * @param int $flags EDIT_XXX flags, as defined for WikiPage::doEditContent.
	 * @param int|bool $baseRevId the revision ID $entity is based on. Saving will
	 * fail if $baseRevId is not the current revision ID.
	 *
	 * @see EntityStore::saveEntity()
	 * @see WikiPage::doEditContent
	 *
	 * @return EntityRevision
	 *
	 * @throws StorageException
	 * @throws PermissionsError
	 */
	public function saveEntity( Entity $entity, $summary, User $user, $flags = 0, $baseRevId = false ) {
		$content = $this->contentFactory->newFromEntity( $entity );

		//TODO: move the logic from EntityContent::save here!
		$status = $content->save( $summary, $user, $flags, $baseRevId );

		if ( !$status->isOK() ) {
			throw new StorageException( $status );
		}

		// As per convention defined by WikiPage, the new revision ID is in the status value:
		$value = $status->getValue();

		/* @var Revision $revision */
		$revision = $value['revision']; //NOTE: EntityContent makes sure this is always set.

		$rev = new EntityRevision( $entity, $revision->getId(), $revision->getTimestamp() );

		$this->dispatcher->dispatch( 'entityUpdated', $rev );

		return $rev;
	}

	/**
	 * @see EntityTitleLookup::getTitleForId
	 *
	 * @param EntityId $entityId
	 *
	 * @return Title|null
	 */
	public function getTitleForId( EntityId $entityId ) {
		return $this->contentFactory->getTitleForId( $entityId );
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
		$title = $this->getTitleForId( $entityId );

		$page = new \WikiPage( $title );
		$ok = $page->doDeleteArticle( $reason, false, 0, true, $error, $user );

		if ( !$ok ) {
			throw new StorageException( 'Faield to delete ' . $entityId->getSerialization(). ': ' . $error );
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
		wfProfileIn( __METHOD__ );

		$revision = Revision::newFromId( $lastRevId );
		if ( !$revision ) {
			wfProfileOut( __METHOD__ );
			return false;
		}

		$pageId = intval( $revision->getPage() );

		// Scan through the revision table
		$dbw = wfGetDB( DB_MASTER );
		$res = $dbw->select( 'revision',
			'rev_user',
			array(
				'rev_page' => $pageId,
				'rev_id > ' . intval( $lastRevId )
				. ' OR rev_timestamp > ' . $dbw->addQuotes( $revision->getTimestamp() ),
				'rev_user != ' . intval( $user->getId() )
				. ' OR rev_user_text != ' . $dbw->addQuotes( $user->getName() ),
			),
			__METHOD__,
			array( 'ORDER BY' => 'rev_timestamp ASC', 'LIMIT' => 1 )
		);

		wfProfileOut( __METHOD__ );
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
		$title = $this->getTitleForId( $id );

		if ( $user->isLoggedIn() && $title && ( $watch != $user->isWatched( $title ) ) ) {
			$fname = __METHOD__;

			// Do this in its own transaction to reduce contention...
			$dbw = wfGetDB( DB_MASTER );
			$dbw->onTransactionIdle( function() use ( $dbw, $title, $watch, $user, $fname ) {
				$dbw->begin( $fname );
				if ( $watch ) {
					\WatchAction::doWatch( $title, $user );
				} else {
					\WatchAction::doUnwatch( $title, $user );
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
		$title = $this->getTitleForId( $id );
		return ( $title && $user->isWatched( $title ) );
	}
}
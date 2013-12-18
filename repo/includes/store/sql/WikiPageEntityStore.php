<?php

namespace Wikibase\store;

use PermissionsError;
use Revision;
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
			$messageKeys = array_map( function( array $error ) {
				return $error[0];
			}, $status->getErrorsArray() );

			//TODO: nicer error! Can we keep the status somehow? Can we make an ErrorPageError sensibly?
			throw new StorageException( implode( ', ', $messageKeys ) );
		}

		// As per convention defined by WikiPage, the new revision ID is in the status value:
		$value = $status->getValue();

		/* @var Revision $revision */
		$revision = isset( $value['revision'] ) ? $value['revision'] : null;

		$rev = new EntityRevision( $entity, $revision->getId(), $revision->getTimestamp() );

		$this->dispatcher->dispatch( 'entityChanged', $entity, $revision->getId() );

		return $rev;
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
		$title = $this->contentFactory->getTitleForId( $entityId );

		$page = new \WikiPage( $title );
		$ok = $page->doDeleteArticle( $reason, false, 0, true, $error, $user );

		if ( !$ok ) {
			throw new StorageException( 'Faield to delete ' . $entityId->getSerialization(). ': ' . $error );
		}

		$this->dispatcher->dispatch( 'entityDeleted', $entityId );
	}
}
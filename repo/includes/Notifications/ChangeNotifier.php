<?php

namespace Wikibase\Repo\Notifications;

use InvalidArgumentException;
use Revision;
use User;
use Wikibase\EntityChange;
use Wikibase\EntityContent;
use Wikibase\Lib\Changes\EntityChangeFactory;

/**
 * Class for generating and submitting change notifications in different situations.
 * This is a helper intended for use in hook handler functions.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class ChangeNotifier {

	/**
	 * @var EntityChangeFactory
	 */
	private $changeFactory;

	/**
	 * @var ChangeTransmitter
	 */
	private $changeTransmitter;

	public function __construct( EntityChangeFactory $changeFactory, ChangeTransmitter $notificationChannel ) {
		$this->changeFactory = $changeFactory;
		$this->changeTransmitter = $notificationChannel;
	}

	/**
	 * This method constructs and sends the appropriate notifications (if any)
	 * when a wiki page containing an EntityContent was deleted.
	 *
	 * @param EntityContent $content
	 * @param User $user
	 * @param string $timestamp timestamp in TS_MW format.
	 *
	 * @throws InvalidArgumentException
	 * @throws ChangeTransmitterException
	 *
	 * @return EntityChange|null
	 */
	public function notifyOnPageDeleted( EntityContent $content, User $user, $timestamp ) {
		wfProfileIn( __METHOD__ );

		if ( $content->isRedirect() ) {
			// TODO: notify the client about changes to redirects!
			return null;
		}

		$change = $this->changeFactory->newFromUpdate( EntityChange::REMOVE, $content->getEntity() );

		$change->setTimestamp( $timestamp );
		$change->setUserId( $user->getId() );
		$change->setMetadataFromUser( $user );

		$this->changeTransmitter->transmitChange( $change );

		wfProfileOut( __METHOD__ );
		return $change;
	}

	/**
	 * This method constructs and sends the appropriate notifications (if any)
	 * when a wiki page containing an EntityContent was undeleted.
	 *
	 * @param Revision $revision
	 *
	 * @return EntityChange|null
	 */
	public function notifyOnPageUndeleted( Revision $revision ) {
		wfProfileIn( __METHOD__ );

		/* @var EntityChange $content */
		$content = $revision->getContent();
		if ( $content->isRedirect() ) {
			// TODO: notify the client about changes to redirects!
			return null;
		}

		$entity = $content->getEntity();

		$change = $this->changeFactory->newFromUpdate( EntityChange::RESTORE, null, $entity );

		$change->setRevisionInfo( $revision );

		$user = User::newFromId( $revision->getUser() );
		$change->setMetadataFromUser( $user );

		$this->changeTransmitter->transmitChange( $change );

		wfProfileOut( __METHOD__ );
		return $change;
	}

	/**
	 * This method constructs and sends the appropriate notifications (if any)
	 * when a wiki page containing an EntityContent was created.
	 *
	 * @param Revision $revision
	 *
	 * @throws InvalidArgumentException
	 * @throws ChangeTransmitterException
	 *
	 * @return EntityChange|null
	 */
	public function notifyOnPageCreated( Revision $revision ) {
		wfProfileIn( __METHOD__ );

		/* @var EntityContent $newContent */
		$newContent = $revision->getContent();

		if ( $newContent->isRedirect() ) {
			// Clients currently don't care about redirected being created.
			// TODO: notify the client about changes to redirects!
			return null;
		}

		$newEntity = $newContent->getEntity();

		$change = $this->changeFactory->newFromUpdate( EntityChange::ADD, null, $newEntity );

		$change->setRevisionInfo( $revision );

		// FIXME: RepoHooks::onRecentChangeSave currently adds to the change later!
		$this->changeTransmitter->transmitChange( $change );

		wfProfileOut( __METHOD__ );
		return $change;
	}

	/**
	 * This method constructs and sends the appropriate notifications (if any)
	 * when a wiki page containing an EntityContent was modified.
	 *
	 * @param Revision $current
	 * @param Revision $parent
	 *
	 * @throws InvalidArgumentException
	 * @throws ChangeTransmitterException
	 *
	 * @return EntityChange|null
	 */
	public function notifyOnPageModified( Revision $current, Revision $parent ) {
		wfProfileIn( __METHOD__ );

		if ( $current->getParentId() !== $parent->getId() ) {
			throw new InvalidArgumentException( '$parent->getId() must be the same as $current->getParentId()!' );
		}

		/* @var EntityContent $newContent */
		$newContent = $current->getContent();

		/* @var EntityContent $oldContent */
		$oldContent = $parent->getContent();

		$change = $this->getChangeForModification( $oldContent, $newContent );

		if ( !$change ) {
			// nothing to do
			return null;
		}

		$change->setRevisionInfo( $current );

		// FIXME: RepoHooks::onRecentChangeSave currently adds to the change later!
		$this->changeTransmitter->transmitChange( $change );

		wfProfileOut( __METHOD__ );
		return $change;
	}

	/**
	 * Returns a EntityChange based on the old and new content object, taking
	 * redirects into consideration.
	 *
	 * @todo: Notify the client about changes to redirects explicitly.
	 *
	 * @param EntityContent $oldContent
	 * @param EntityContent $newContent
	 *
	 * @return EntityChange|null
	 */
	private function getChangeForModification( EntityContent $oldContent, EntityContent $newContent ) {
		$newEntity = $newContent->isRedirect() ? null : $newContent->getEntity();
		$oldEntity = $oldContent->isRedirect() ? null : $oldContent->getEntity();

		if ( $newEntity === null && $oldEntity === null ) {
			// Old and new versions are redirects. Nothing to do.
			return null;
		} elseif ( $newEntity === null ) {
			// The new version is a redirect. For now, treat that as a deletion.
			$action = EntityChange::REMOVE;
		} elseif ( $oldEntity === null ) {
			// The old version is a redirect. For now, treat that like restoring the entity.
			$action = EntityChange::RESTORE;
		} else {
			// No redirects involved
			$action = EntityChange::UPDATE;
		}

		$change = $this->changeFactory->newFromUpdate( $action, $oldEntity, $newEntity );
		return $change;
	}

}

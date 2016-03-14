<?php

namespace Wikibase\Repo\Notifications;

use Hooks;
use InvalidArgumentException;
use Revision;
use User;
use Wikibase\EntityChange;
use Wikibase\EntityContent;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikimedia\Assert\Assert;

/**
 * Class for generating and submitting change notifications in different situations.
 * This is a helper intended for use in hook handler functions.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class ChangeNotifier {

	/**
	 * @var EntityChangeFactory
	 */
	private $changeFactory;

	/**
	 * @var ChangeTransmitter[]
	 */
	private $changeTransmitters;

	/**
	 * @param EntityChangeFactory $changeFactory
	 * @param ChangeTransmitter[] $changeTransmitters
	 */
	public function __construct( EntityChangeFactory $changeFactory, array $changeTransmitters ) {
		Assert::parameterElementType(
			ChangeTransmitter::class,
			$changeTransmitters,
			'$changeTransmitters'
		);

		$this->changeFactory = $changeFactory;
		$this->changeTransmitters = $changeTransmitters;
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
	 * @return EntityChange|null
	 */
	public function notifyOnPageDeleted( EntityContent $content, User $user, $timestamp ) {
		if ( $content->isRedirect() ) {
			// TODO: notify the client about changes to redirects!
			return null;
		}

		$change = $this->changeFactory->newFromUpdate( EntityChange::REMOVE, $content->getEntity() );

		$change->setTimestamp( $timestamp );
		$change->setMetadataFromUser( $user );

		$this->transmitChange( $change );

		Hooks::run( 'WikibaseChange', array( $change ) );
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
		/** @var EntityContent $content */
		$content = $revision->getContent();

		if ( $content->isRedirect() ) {
			// TODO: notify the client about changes to redirects!
			return null;
		}

		$change = $this->changeFactory->newFromUpdate( EntityChange::RESTORE, null, $content->getEntity() );
		$change->setRevisionInfo( $revision );
		// We don't want the change entries of newly undeleted pages to have
		// the timestamp of the original change.
		$change->setTimestamp( wfTimestampNow() );

		$user = User::newFromId( $revision->getUser() );
		$change->setMetadataFromUser( $user );

		$this->transmitChange( $change );

		Hooks::run( 'WikibaseChange', array( $change ) );
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
	 * @return EntityChange|null
	 */
	public function notifyOnPageCreated( Revision $revision ) {
		/** @var EntityContent $content */
		$content = $revision->getContent();

		if ( $content->isRedirect() ) {
			// Clients currently don't care about redirected being created.
			// TODO: notify the client about changes to redirects!
			return null;
		}

		$change = $this->changeFactory->newFromUpdate( EntityChange::ADD, null, $content->getEntity() );

		$change->setRevisionInfo( $revision );

		// FIXME: RepoHooks::onRecentChangeSave currently adds to the change later!
		$this->transmitChange( $change );

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
	 * @return EntityChange|null
	 */
	public function notifyOnPageModified( Revision $current, Revision $parent ) {
		if ( $current->getParentId() !== $parent->getId() ) {
			throw new InvalidArgumentException( '$parent->getId() must be the same as $current->getParentId()!' );
		}

		$change = $this->getChangeForModification( $parent->getContent(), $current->getContent() );

		if ( !$change ) {
			// nothing to do
			return null;
		}

		$change->setRevisionInfo( $current );

		// FIXME: RepoHooks::onRecentChangeSave currently adds to the change later!
		$this->transmitChange( $change );

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
		$oldEntity = $oldContent->isRedirect() ? null : $oldContent->getEntity();
		$newEntity = $newContent->isRedirect() ? null : $newContent->getEntity();

		if ( $oldEntity === null && $newEntity === null ) {
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

	/**
	 * Transmit changes via all registered transmitters
	 *
	 * @param EntityChange $change
	 */
	private function transmitChange( EntityChange $change ) {
		foreach ( $this->changeTransmitters as $transmitter ) {
			$transmitter->transmitChange( $change );
		}
	}

}

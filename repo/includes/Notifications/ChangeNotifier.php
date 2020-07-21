<?php

namespace Wikibase\Repo\Notifications;

use CentralIdLookup;
use InvalidArgumentException;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use User;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Repo\Content\EntityContent;
use Wikimedia\Assert\Assert;

/**
 * Class for generating and submitting change notifications in different situations.
 * This is a helper intended for use in hook handler functions.
 *
 * @license GPL-2.0-or-later
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
	 * @var CentralIdLookup|null
	 */
	private $centralIdLookup;

	/**
	 * @param EntityChangeFactory $changeFactory
	 * @param ChangeTransmitter[] $changeTransmitters
	 * @param CentralIdLookup|null $centralIdLookup CentralIdLookup, or null if
	 *   this repository is not connected to a central user system,
	 *   see CentralIdLookup::factoryNonLocal.
	 */
	public function __construct(
		EntityChangeFactory $changeFactory,
		array $changeTransmitters,
		?CentralIdLookup $centralIdLookup
	) {
		Assert::parameterElementType(
			ChangeTransmitter::class,
			$changeTransmitters,
			'$changeTransmitters'
		);

		$this->changeFactory = $changeFactory;
		$this->changeTransmitters = $changeTransmitters;
		$this->centralIdLookup = $centralIdLookup;
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

		/** @var RepoEntityChange $change */
		$change = $this->changeFactory->newFromUpdate( EntityChange::REMOVE, $content->getEntity() );
		'@phan-var RepoEntityChange $change';
		$change->setTimestamp( $timestamp );
		$change->setMetadataFromUser(
			$user,
			$this->getCentralUserId( $user )
		);

		$this->transmitChange( $change );

		return $change;
	}

	/**
	 * This method constructs and sends the appropriate notifications (if any)
	 * when a wiki page containing an EntityContent was undeleted.
	 *
	 * @param RevisionRecord $revisionRecord
	 *
	 * @return EntityChange|null
	 */
	public function notifyOnPageUndeleted( RevisionRecord $revisionRecord ) {
		/** @var EntityContent $content */
		$content = $revisionRecord->getContent( SlotRecord::MAIN );
		'@phan-var EntityContent $content';

		if ( $content->isRedirect() ) {
			// TODO: notify the client about changes to redirects!
			return null;
		}

		/** @var RepoEntityChange $change */
		$change = $this->changeFactory->newFromUpdate( EntityChange::RESTORE, null, $content->getEntity() );
		'@phan-var RepoEntityChange $change';

		$change->setRevisionInfo(
			$revisionRecord,
			/* Will get set below in setMetadataFromUser */ 0
		);

		// We don't want the change entries of newly undeleted pages to have
		// the timestamp of the original change.
		$change->setTimestamp( wfTimestampNow() );

		$user = User::newFromIdentity( $revisionRecord->getUser() );
		$change->setMetadataFromUser(
			$user,
			$this->getCentralUserId( $user )
		);

		$this->transmitChange( $change );

		return $change;
	}

	/**
	 * This method constructs and sends the appropriate notifications (if any)
	 * when a wiki page containing an EntityContent was created.
	 *
	 * @param RevisionRecord $revisionRecord
	 *
	 * @throws InvalidArgumentException
	 * @throws ChangeTransmitterException
	 * @return EntityChange|null
	 */
	public function notifyOnPageCreated( RevisionRecord $revisionRecord ) {
		/** @var EntityContent $content */
		$content = $revisionRecord->getContent( SlotRecord::MAIN );
		'@phan-var EntityContent $content';

		if ( $content->isRedirect() ) {
			// Clients currently don't care about redirected being created.
			// TODO: notify the client about changes to redirects!
			return null;
		}

		/** @var RepoEntityChange $change */
		$change = $this->changeFactory->newFromUpdate( EntityChange::ADD, null, $content->getEntity() );
		'@phan-var RepoEntityChange $change';
		$change->setRevisionInfo(
			$revisionRecord,
			$this->getCentralUserId( User::newFromIdentity( $revisionRecord->getUser() ) )
		);

		// FIXME: RepoHooks::onRecentChangeSave currently adds to the change later!
		$this->transmitChange( $change );

		return $change;
	}

	/**
	 * This method constructs and sends the appropriate notifications (if any)
	 * when a wiki page containing an EntityContent was modified.
	 *
	 * @param RevisionRecord $current
	 * @param RevisionRecord $parent
	 *
	 * @throws InvalidArgumentException
	 * @throws ChangeTransmitterException
	 * @return EntityChange|null
	 */
	public function notifyOnPageModified( RevisionRecord $current, RevisionRecord $parent ) {
		if ( $current->getParentId() !== $parent->getId() ) {
			throw new InvalidArgumentException( '$parent->getId() must be the same as $current->getParentId()!' );
		}

		$change = $this->getChangeForModification(
			$parent->getContent( SlotRecord::MAIN ),
			$current->getContent( SlotRecord::MAIN )
		);

		if ( !$change ) {
			// nothing to do
			return null;
		}

		$change->setRevisionInfo(
			$current,
			$this->getCentralUserId( User::newFromIdentity( $current->getUser() ) )
		);

		// FIXME: RepoHooks::onRecentChangeSave currently adds to the change later!
		$this->transmitChange( $change );

		return $change;
	}

	/**
	 * @param User $user Repository user
	 *
	 * @return int Central user ID, or 0
	 */
	private function getCentralUserId( User $user ) {
		if ( $this->centralIdLookup ) {
			return $this->centralIdLookup->centralIdFromLocalUser( $user );
		}

		return 0;
	}

	/**
	 * Returns a EntityChange based on the old and new content object, taking
	 * redirects into consideration.
	 *
	 * @todo Notify the client about changes to redirects explicitly.
	 *
	 * @param EntityContent $oldContent
	 * @param EntityContent $newContent
	 *
	 * @return RepoEntityChange|null
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

		/** @var RepoEntityChange $change */
		$change = $this->changeFactory->newFromUpdate( $action, $oldEntity, $newEntity );
		'@phan-var RepoEntityChange $change';
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

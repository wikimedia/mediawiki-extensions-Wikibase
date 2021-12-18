<?php

namespace Wikibase\Repo\Notifications;

use InvalidArgumentException;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\User\UserIdentity;
use Wikibase\Lib\Changes\EntityChange;
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
	 * @var ChangeTransmitter[]
	 */
	private $changeTransmitters;

	/**
	 * @var WikiPageActionEntityChangeFactory
	 */
	private $changeFactory;

	public function __construct(
		WikiPageActionEntityChangeFactory $changeFactory,
		array $changeTransmitters
	) {
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
	 * @param UserIdentity $user
	 * @param string $timestamp timestamp in TS_MW format.
	 *
	 * @throws InvalidArgumentException
	 * @throws ChangeTransmitterException
	 * @return EntityChange|null
	 */
	public function notifyOnPageDeleted( EntityContent $content, UserIdentity $user, $timestamp ) {
		if ( $content->isRedirect() ) {
			// TODO: notify the client about changes to redirects!
			return null;
		}

		$change = $this->changeFactory->newForPageDeleted( $content, $user, $timestamp );

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

		$change = $this->changeFactory->newForPageUndeleted( $content, $revisionRecord );

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

		$change = $this->changeFactory->newForPageCreated( $content, $revisionRecord );

		// FIXME: RecentChangeSaveHookHandler currently adds to the change later!
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
		/** @var EntityContent $parentContent */
		$parentContent = $parent->getContent( SlotRecord::MAIN );
		'@phan-var EntityContent $parentContent';
		$content = $current->getContent( SlotRecord::MAIN );

		if ( $parentContent->isRedirect() && $content->isRedirect() ) {
			return null; // TODO: notify the client about changes to redirects!
		}

		$change = $this->changeFactory->newForPageModified( $current, $parentContent );

		// FIXME: RepoHooks::onRecentChangeSave currently adds to the change later!
		$this->transmitChange( $change );

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

<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Notifications;

use CentralIdLookup;
use Exception;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\User\UserIdentity;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lib\Changes\ChangeRow;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Repo\Content\EntityContent;

/**
 * Factory for creating EntityChange objects for repo wiki page actions that clients need to be notified for.
 *
 * @license GPL-2.0-or-later
 */
class WikiPageActionEntityChangeFactory {

	private $changeFactory;

	private $centralIdLookup;

	public function __construct(
		EntityChangeFactory $changeFactory,
		?CentralIdLookup $centralIdLookup
	) {
		$this->changeFactory = $changeFactory;
		$this->centralIdLookup = $centralIdLookup;
	}

	public function newForPageDeleted( EntityContent $content, UserIdentity $user, string $timestamp ): EntityChange {
		$change = $this->changeFactory->newFromUpdate( EntityChange::REMOVE, $content->getEntity() );
		$change->setTimestamp( $timestamp );
		$this->setEntityChangeUserInfo(
			$change,
			$user,
			$this->getCentralUserId( $user )
		);

		return $change;
	}

	public function newForPageUndeleted( EntityContent $content, RevisionRecord $revisionRecord ): EntityChange {
		$change = $this->changeFactory->newFromUpdate( EntityChange::RESTORE, null, $content->getEntity() );

		$this->setEntityChangeRevisionInfo(
			$change,
			$revisionRecord,
			/* Will get set below in setMetadataFromUser */ 0
		);

		// We don't want the change entries of newly undeleted pages to have
		// the timestamp of the original change.
		$change->setTimestamp( wfTimestampNow() );

		$user = $revisionRecord->getUser();
		$this->setEntityChangeUserInfo(
			$change,
			$user,
			$this->getCentralUserId( $user )
		);

		return $change;
	}

	public function newForPageCreated( EntityContent $content, RevisionRecord $revisionRecord ): EntityChange {
		$change = $this->changeFactory->newFromUpdate( EntityChange::ADD, null, $content->getEntity() );
		$this->setEntityChangeRevisionInfo(
			$change,
			$revisionRecord,
			$this->getCentralUserId( $revisionRecord->getUser() )
		);

		return $change;
	}

	public function newForPageModified( RevisionRecord $currentRevision, EntityContent $parentRevisionContent ): EntityChange {
		/** @var EntityContent $currentRevisionContent */
		$currentRevisionContent = $currentRevision->getContent( SlotRecord::MAIN );
		'@phan-var EntityContent $currentRevisionContent';

		$change = $this->getChangeForModification(
			$parentRevisionContent->isRedirect() ? null : $parentRevisionContent->getEntity(),
			$currentRevisionContent->isRedirect() ? null : $currentRevisionContent->getEntity()
		);

		$this->setEntityChangeRevisionInfo(
			$change,
			$currentRevision,
			$this->getCentralUserId( $currentRevision->getUser() )
		);

		return $change;
	}

	/**
	 * Returns a EntityChange based on the old and new content object, taking
	 * redirects into consideration.
	 */
	private function getChangeForModification( ?EntityDocument $oldEntity, ?EntityDocument $newEntity ): EntityChange {
		if ( $newEntity === null ) {
			// The new version is a redirect. For now, treat that as a deletion.
			$action = EntityChange::REMOVE;
		} elseif ( $oldEntity === null ) {
			// The old version is a redirect. For now, treat that like restoring the entity.
			$action = EntityChange::RESTORE;
		} else {
			// No redirects involved
			$action = EntityChange::UPDATE;
		}

		return $this->changeFactory->newFromUpdate( $action, $oldEntity, $newEntity );
	}

	/**
	 * @param UserIdentity $user Repository user
	 *
	 * @return int Central user ID, or 0
	 */
	private function getCentralUserId( UserIdentity $user ): int {
		if ( $this->centralIdLookup ) {
			return $this->centralIdLookup->centralIdFromLocalUser( $user );
		}

		return 0;
	}

	/**
	 * @param EntityChange $change
	 * @param UserIdentity $user User that made change
	 * @param int $centralUserId Central user ID, or 0 if unknown or not applicable
	 *   (see docs/change-propagation.md)
	 */
	private function setEntityChangeUserInfo( EntityChange $change, UserIdentity $user, $centralUserId ): void {
		$change->addUserMetadata(
			$user->getId(),
			$user->getName(),
			$centralUserId
		);
	}

	/**
	 * @param EntityChange $change
	 * @param RevisionRecord $revision Revision to populate EntityChange from
	 * @param int $centralUserId Central user ID, or 0 if unknown or not applicable
	 *   (see docs/change-propagation.md)
	 */
	private function setEntityChangeRevisionInfo( EntityChange $change, RevisionRecord $revision, int $centralUserId ): void {
		$change->setFields( [
			ChangeRow::REVISION_ID => $revision->getId(),
			ChangeRow::TIME => $revision->getTimestamp(),
		] );

		if ( !$change->hasField( ChangeRow::OBJECT_ID ) ) {
			throw new Exception(
				'EntityChange::setRevisionInfo() called without calling setEntityId() first!'
			);
		}

		$comment = $revision->getComment();
		$change->setMetadata( [
			'page_id' => $revision->getPageId(),
			'parent_id' => $revision->getParentId(),
			'comment' => $comment ? $comment->text : null,
			'rev_id' => $revision->getId(),
		] );

		$user = $revision->getUser();
		$change->addUserMetadata(
			$user ? $user->getId() : 0,
			$user ? $user->getName() : '',
			$centralUserId
		);
	}

}

<?php

namespace Wikibase\Repo\Notifications;

use Exception;
use MediaWiki\Revision\RevisionRecord;
use RecentChange;
use User;
use Wikibase\Lib\Changes\EntityChange;

/**
 * @license GPL-2.0-or-later
 */
class RepoEntityChange extends EntityChange {

	/**
	 * @param RecentChange $rc
	 * @param int $centralUserId Central user ID, or 0 if unknown or not applicable
	 *   (see docs/change-propagation.wiki)
	 *
	 * @todo rename to setRecentChangeInfo
	 */
	public function setMetadataFromRC( RecentChange $rc, $centralUserId ) {
		$this->setFields( [
			'revision_id' => $rc->getAttribute( 'rc_this_oldid' ),
			'time' => $rc->getAttribute( 'rc_timestamp' ),
		] );

		$this->setMetadata( [
			'bot' => $rc->getAttribute( 'rc_bot' ),
			'page_id' => $rc->getAttribute( 'rc_cur_id' ),
			'rev_id' => $rc->getAttribute( 'rc_this_oldid' ),
			'parent_id' => $rc->getAttribute( 'rc_last_oldid' ),
			'comment' => $rc->getAttribute( 'rc_comment' ),
		] );

		$this->addUserMetadata(
			$rc->getAttribute( 'rc_user' ),
			$rc->getAttribute( 'rc_user_text' ),
			$centralUserId
		);
	}

	/**
	 * @todo rename to setUserInfo
	 *
	 * @param User $user User that made change
	 * @param int $centralUserId Central user ID, or 0 if unknown or not applicable
	 *   (see docs/change-propagation.wiki)
	 */
	public function setMetadataFromUser( User $user, $centralUserId ) {
		$this->addUserMetadata(
			$user->getId(),
			$user->getName(),
			$centralUserId
		);

		// TODO: init page_id etc in getMetadata, not here!
		$metadata = array_merge( [
				'page_id' => 0,
				'rev_id' => 0,
				'parent_id' => 0,
			],
			$this->getMetadata()
		);

		$this->setMetadata( $metadata );
	}

	/**
	 * @param RevisionRecord $revision Revision to populate EntityChange from
	 * @param int $centralUserId Central user ID, or 0 if unknown or not applicable
	 *   (see docs/change-propagation.wiki)
	 */
	public function setRevisionInfo( RevisionRecord $revision, $centralUserId ) {
		$this->setFields( [
			'revision_id' => $revision->getId(),
			'time' => $revision->getTimestamp(),
		] );

		if ( !$this->hasField( 'object_id' ) ) {
			throw new Exception(
				'EntityChange::setRevisionInfo() called without calling setEntityId() first!'
			);
		}

		$comment = $revision->getComment();
		$this->setMetadata( [
			'page_id' => $revision->getPageId(),
			'parent_id' => $revision->getParentId(),
			'comment' => $comment ? $comment->text : null,
			'rev_id' => $revision->getId(),
		] );

		$user = $revision->getUser();
		$this->addUserMetadata(
			$user ? $user->getId() : 0,
			$user ? $user->getName() : '',
			$centralUserId
		);
	}

}

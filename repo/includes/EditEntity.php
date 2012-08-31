<?php

namespace Wikibase;

use \Wikibase\Entity as Entity;
use Status;

/**
 * Parts of the edit interface for entities..
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class EditEntity {

	/**
	 * The original entity we use for creating the diff
	 * @since 0.1
	 * @var Entity
	 */
	protected $mOriginalEntity = null;

	/**
	 * The entity we apply the diff and then it will be "patched"
	 * @since 0.1
	 * @var Entity
	 */
	protected $mPatchedEntity = null;

	/**
	 * @since 0.1
	 * @var Revision
	 */
	protected $mBaseRevisionId = false;

	/**
	 * @since 0.1
	 * @var Revision
	 */
	protected $mApplicableRevisionId = false;

	/**
	 * @since 0.1
	 * @var Status
	 */
	protected $mStatus = null;

	/**
	 * @since 0.1
	 * @var Empty
	 */
	protected $mEmpty = null;

	/**
	 * @since 0.1
	 * @var Empty
	 */
	protected $mUserWasLastToEdit = null;

	/**
	 * Factory method for new edit entities
	 *
	 * @since 0.1
	 *
	 * @param Entity $article an entity to use as the the source
	 * @param User $user the editing user
	 * @param false|int $baseRevisionId the revision id used to get the base entity to compare against for the first diff
	 * @param false|int $lastRevisionId the revision id used to get the last entity to compare against for the applicable diff and patching
	 *
	 * @return null|EditEntity a valid EditEntity or null if the creation failed
	 */
	public static function newEditEntity( Entity &$originalEntity, \User $user, $baseRevisionId = false, $applicableRevisionId = false ) {
		try {
			$editEntity = new EditEntity( $originalEntity );
			$editEntity->apply( $user, $baseRevisionId, $applicableRevisionId );
		}
		catch ( EditEntityException $e ) {
			$editEntity = null;
		}
		return $editEntity;
	}

	/**
	 * @since 0.1
	 *
	 * @param Entity $article an entity to use as the the source
	 */
	public function __construct( Entity &$originalEntity ) {
		$this->mStatus = \Status::newGood();
		$this->mOriginalEntity = $originalEntity;
	}

	/**
	 * Setup to be run after the initial construction
	 *
	 * @since 0.1
	 *
	 * @param User $user the editing user
	 * @param false|int $baseRevisionId the revision id used to get the base entity to compare against for the first diff
	 * @param false|int $lastRevisionId the revision id used to get the last entity to compare against for the applicable diff and patching
	 */
	public function apply( \User $user, $baseRevisionId = false, $applicableRevisionId = false ) {
		$done = false;

		// This is for later versions where we need revisions to exist for further testing,
		// that is we are checking their content and building diffs and patching them.
		// For now we're only checking if they are there.
		if ( $baseRevisionId !== false ) {
			$baseRevision = \Revision::newFromId( $baseRevisionId );
			if ( !isset( $baseRevision ) ) {
				throw new EditEntityException( 'No base revision was found', 'wikibase-no-revision' );
			}
		}
		if ( $applicableRevisionId !== false ) {
			$applicableRevision = \Revision::newFromId( $applicableRevisionId );
			if ( !isset( $applicableRevision ) ) {
				throw new EditEntityException( 'No applicable revision was found', 'wikibase-no-revision' );
			}
		}

		//@todo: check whether $baseRevision refers to the same item id as the original entity
		//@todo: check whether $applicableRevision refers to the same item id as the original entity
		//@todo: make sure the $baseRevision is older than $applicableRevision

		$this->mPatchedEntity = $this->mOriginalEntity;
		$this->mUserWasLastToEdit = true;

		$this->mStatus = \Status::newGood('ok');

		// base revision is the last one
		if ( $baseRevisionId !== false && $applicableRevisionId !== false && $baseRevisionId === $applicableRevisionId ) {
			$this->mStatus = \Status::newGood('only-to-edit');
			$done = true;
		}

		// check if the user was the last one to edit since the given revision id
		if ( !$done && $baseRevisionId !== false ) {
			$this->mUserWasLastToEdit = self::userWasLastToEdit( $user->getId(), $baseRevisionId );

			if ( $this->mUserWasLastToEdit ) {
				$this->mStatus = \Status::newGood('last-to-edit');
				$done = true;
			}
		}
	}

	/**
	 * Get the base revisions id
	 *
	 * @since 0.1
	 *
	 * @return false|int the id for the revision or false if not yet available
	 */
	public function getBaseRevisionId() {
		return $this->mBaseRevisionId;
	}

	/**
	 * Get the applicable revisions id
	 *
	 * @since 0.1
	 *
	 * @return false|int the id for the revision or false if not yet available
	 */
	public function getApplicableRevisionId() {
		return $this->mApplicableRevisionId;
	}

	/**
	 * Get the original entity used to create the base patchset
	 *
	 * @since 0.1
	 *
	 * @return null|Entity the entity or null if not yet available
	 */
	public function getOriginalEntity() {
		return $this->mOriginalEntity;
	}

	/**
	 * Get the patched entity, the one after applying the diff
	 *
	 * Note that this entity will only be "patched" after the method applyPatch is run.
	 *
	 * @since 0.1
	 *
	 * @return null|Entity the entity or null if not yet available
	 */
	public function getPatchedEntity() {
		return $this->mPatchedEntity;
	}

	/**
	 * Get the status
	 *
	 * Note that this entity will only be "patched" after the method applyPatch is run.
	 *
	 * @since 0.1
	 *
	 * @return null|Status
	 */
	public function getStatus() {
		return $this->mStatus;
	}

	/**
	 * Does the patch covers all items from the initial diff?
	 * 
	 * @since 0.1
	 * 
	 * @return null|Diff the entity or null if not yet available
	 */
	public function isSuccess() {
		if ( $this->mUserWasLastToEdit === true ) {
			return true;
		}
		else {
			return /* $this->isComplete() */ false;
		}
	}

	/**
	 * Check if no edits were made by other users since the given revision. Limit to 50 revisions for the
	 * sake of performance.
	 *
	 * This makes the assumption that revision ids are monotonically increasing, and also neglects the fact
	 * that conflicts are not only with the user himself.
	 *
	 * Note that this is a variation over the same idea that is used in EditPage::userWasLastToEdit() but
	 * with the difference that this one is using the revision and not the timestamp.
	 *
	 * TODO: Change this into an instance level member and store the ids for later lookup.
	 * Use those ids for full lookup of the content and create applicable diffs and check if they are empty.
	 *
	 * @param int|null $user the users numeric identifier
	 * @param int|false $lastRevId the revision the user supplied
	 *
	 * @return bool
	 */
	public static function userWasLastToEdit( $userId = false, $lastRevId = false ) {

		// If the lastRevId is missing then skip all further test and give false.
		// Note that without a revision id it will not be possible to do patching.
		if ( $lastRevId === false ) {
			return false;
		}
		else {
			$revision = \Revision::newFromId( $lastRevId );
			if ( !isset( $revision ) ) {
				return false;
			}
		}

		// If the userId is missing then skip all further test and give false.
		// It is only the user id that is used later on.
		if ( $userId === false ) {
			return false;
		}
		else {
			$user = \User::newFromId( $userId );
			if ( !isset( $user ) ) {
				return false;
			}
		}

		// If the title is missing then skip all further test and give false.
		// There must be a title so we can get an article id
		$title = $revision->getTitle();
		if ( !isset( $title ) ) {
			return false;
		}

		// Scan through the revision table
		$dbw = wfGetDB( DB_MASTER );
		$res = $dbw->select( 'revision',
			'rev_user',
			array(
				'rev_page' => $title->getArticleID(),
				'rev_id > ' . intval( $lastRevId )
					. ' OR rev_timestamp > ' . $dbw->addQuotes( $revision->getTimestamp() ),
				'rev_user != ' . intval( $userId )
					. ' OR rev_user_text != ' . $dbw->addQuotes( $user->getName() ),
			),
			__METHOD__,
			array( 'ORDER BY' => 'rev_timestamp ASC', 'LIMIT' => 1 ) );
		// Traversable, not countable ;/
		$count = 0;
		foreach ( $res as $row ) {
			$count++;
		}
		return $count === 0;
	}

}

class EditEntityException extends \UsageException {}
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
	 * @var Diff
	 */
	protected $mBaseDiff = null;

	/**
	 * @since 0.1
	 * @var Diff
	 */
	protected $mApplicableDiff = null;

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
	 * Factory method for new edit entities
	 *
	 * @since 0.1
	 *
	 * @param Entity $article an entity to use as the the source
	 * @param false|int $baseRevisionId the revision id used to get the base entity to compare against for the first diff
	 * @param false|int $lastRevisionId the revision id used to get the last entity to compare against for the applicable diff and patching
	 *
	 * @return null|EditEntity a valid EditEntity or null if the creation failed
	 */
	public static function newEditEntity( Entity $originalEntity, $baseRevisionId = false, $applicableRevisionId = false ) {
		try {
			$editEntity = new EditEntity( $originalEntity, $baseRevisionId, $applicableRevisionId );
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
	 * @param false|int $baseRevisionId the revision id used to get the base entity to compare against for the first diff
	 * @param false|int $lastRevisionId the revision id used to get the last entity to compare against for the applicable diff and patching
	 */
	public function __construct( Entity &$originalEntity, $baseRevisionId = false, $applicableRevisionId = false ) {
		$this->mStatus = Status::newGood();
		$this->mOriginalEntity = $originalEntity;
		if ( $baseRevisionId !== false && $applicableRevisionId !== false && $baseRevisionId === $applicableRevisionId ) {
			$this->mPatchedEntity = $originalEntity;
		}
		else {
			$empty = null;
			if ( $baseRevisionId !== false ) {
				$empty = $this->makeBaseDiff( $baseRevisionId );
			}

			if ( !$empty && $applicableRevisionId !== false ) {
				$empty = $this->makeApplicableDiff( $applicableRevisionId );
			}

			if ( !$empty && $baseRevisionId !== false && $applicableRevisionId !== false ) {
				$this->applyPatch();
			}

			if ( isset( $empty ) ) {
				$this->mEmpty = $empty;
			}
		}
	}

	/**
	 * Get the entity given the revision
	 *
	 * @since 0.1
	 *
	 * @param int $revisionId the id of the revision to get the content for
	 *
	 * @return Entity
	 *
	 * @throw wikibase-no-revision
	 * @throw wikibase-no-revision-id
	 * @throw wikibase-wrong-content-model
	 */
	public static function findEntity( $revisionId ) {
		$revision = \Revision::newFromId( intval( $revisionId ) );

		if ( $revision === null ) {
			return null;
		}

		$content = $revision->getContent();

		if ( !in_array( $content->getModel(), array( CONTENT_MODEL_WIKIBASE_ITEM ), true ) ) {
			return null;
		}

		return $content->getEntity();
	}

	/**
	 * Make a diff against a revision
	 *
	 * @since 0.1
	 *
	 * @param false|int $baseRevisionId the id of the revision to compare against
	 *
	 * @return bool if the diff is empty
	 */
	protected function makeBaseDiff( $baseRevisionId = false ) {
		if ( $baseRevisionId === false ) {
			throw new EditEntityException( 'No revision id was found', 'wikibase-no-revision-id' );
		}

		if ( $this->mBaseRevisionId !== $baseRevisionId ) {
			$baseEntity = static::findEntity( $baseRevisionId );
			if ( !isset( $baseEntity ) ) {
				throw new EditEntityException( 'There is no entity1', 'wikibase-no-entity1' );
			}
			$this->mBaseRevisionId = $baseRevisionId;
			$this->mBaseDiff = $baseEntity->getDiff( $this->mOriginalEntity );
		}

		if ( !isset( $this->mBaseDiff ) ) {
			throw new EditEntityException( 'There is no base diff', 'wikibase-no-base-diff' );
		}

		return $this->mBaseDiff->isEmpty();
	}

	/**
	 * Make an applicable diff against a revision
	 *
	 * @since 0.1
	 *
	 * @param false|int $lastRevId the id of the revision to compare against
	 *
	 * @return bool if the diff is empty
	 */
	protected function makeApplicableDiff( $applicableRevisionId = false ) {
		if ( $applicableRevisionId === false ) {
			throw new EditEntityException( 'No revision id was found', 'wikibase-no-revision-id' );
		}

		if ( $this->mBaseDiff === null ) {
			throw new EditEntityException( 'There is no base diff', 'wikibase-no-base-diff' );
		}

		if ( $this->mApplicableRevisionId !== $applicableRevisionId ) {
			$applicableEntity = static::findEntity( $applicableRevisionId );
			if ( !isset( $applicableEntity ) ) {
				throw new EditEntityException( 'There is no entity2', 'wikibase-no-entity2' );
			}
			$this->mApplicableRevisionId = $applicableRevisionId;
			$this->mApplicableDiff = $this->mBaseDiff->getApplicableDiff( $applicableEntity->toArray() );
			$this->mPatchedEntity = $applicableEntity;
		}

		if ( !isset( $this->mApplicableDiff ) ) {
			throw new EditEntityExceptionn( 'There is no last applicable diff', 'wikibase-no-applicable-diff' );
		}

		return $this->mApplicableDiff->isEmpty();
	}

	/**
	 * Apply the previously generated applicable diff
	 *
	 * @since 0.1
	 *
	 * @param null|Entity $entity an entity to update, or use the stored entity from the applicable call
	 *
	 * @return Entity
	 */
	protected function applyPatch() {

		if ( $this->mPatchedEntity === null ) {
			throw new EditEntityException( 'There is no entity to patch', 'wikibase-no-entity' );
		}

		$this->mApplicableDiff->apply($this->mPatchedEntity );
		return $this->mPatchedEntity;
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
	 * Get the diff against the base revision
	 *
	 * @since 0.1
	 *
	 * @return null|Diff the diff or null if not yet available
	 */
	public function getBaseDiff() {
		return $this->mBaseDiff;
	}

	/**
	 * Get the applicable diff against the last revision
	 *
	 * @since 0.1
	 *
	 * @return null|Diff the diff or null if not yet available
	 */
	public function getApplicableDiff() {
		return $this->mApplicableDiff;
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
	 * Does any of the diffs evaluated to empty?
	 * 
	 * @since 0.1
	 * 
	 * @return bool
	 */
	public function isEmpty() {
		return $this->mEmpty;
	}

	/**
	 * Does the patch covers all items from the initial diff?
	 * 
	 * @since 0.1
	 * 
	 * @return null|Diff the entity or null if not yet available
	 */
	public function isComplete() {
		// note that this isn't correct
		return $this->mBaseDiff->getAddedValues();
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
			$revision = Revision::newFromId( $lastRevId );
			if ( !isset( $revision ) ) {
				return false;
			}
		}

		// If the userId is missing then skip all further test and give false.
		// It is only the user id that is used later on.
		if ( $userId === false ) {
			return false;
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
			),
			__METHOD__,
			array( 'ORDER BY' => 'rev_id ASC', 'LIMIT' => 50 ) );
		foreach ( $res as $row ) {
			if ( $row->rev_user != $userId ) {
				return false;
			}
		}

		// If we're here there was no intervening edits from someone else
		return true;
	}

	/**
	 * Check if no edits were made by other users since the given revision that creates applicable diffs
	 * that interferes with the users current diff. Limit to 50 revisions for the sake of performance.
	 *
	 * This makes the assumption that revision ids are monotonically increasing.
	 *
	 * Note that this is a variation over the same idea that is used in EditPage::userWasLastToEdit() but
	 * with the difference that this one is using the revision and not the timestamp, and that this one
	 * also checks the applicable diffs.
	 *
	 * @param int|null $user the users numeric identifier
	 * @param int|false $lastRevId the revision the user supplied
	 *
	 * @return bool
	 */
	public static function userHasOnlyCleanEdits( $userId = false, $lastRevId = false ) {
		// TODO: Still missing a valid implementation
		// Without this method the user will have edit conflicts with himself.
		return false;
	}
}

class EditEntityException extends \UsageException {}
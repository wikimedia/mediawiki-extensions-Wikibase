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
	 * The new entity after patching
	 * @since 0.1
	 * @var Entity
	 */
	protected $mPatchedEntity = null;

	/**
	 * The original we use for creating the diff
	 * @since 0.1
	 * @var Entity
	 */
	protected $mOriginalEntity = null;

	/**
	 * The entity we use for creating the base diff
	 * @since 0.1
	 * @var Entity
	 */
	protected $mBaseEntity = null;

	/**
	 * The aentity we use for creating the applicable diff
	 * @since 0.1
	 * @var Entity
	 */
	protected $mApplicableEntity = null;

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
	public function __construct( Entity $originalEntity, $baseRevisionId = false, $applicableRevisionId = false ) {
		$this->mStatus = Status::newGood();
		$this->mOriginalEntity = $originalEntity;

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
			$this->mBaseEntity = static::findEntity( $baseRevisionId );
			if ( !isset( $this->mBaseEntity ) ) {
				throw new EditEntityException( 'There is no entity1', 'wikibase-no-entity' );
			}
			$this->mBaseRevisionId = $baseRevisionId;
			$this->mBaseDiff = $this->mBaseEntity->getDiff( $this->mOriginalEntity );
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
			$this->mApplicableEntity = static::findEntity( $applicableRevisionId );
			if ( !isset( $this->mApplicableEntity ) ) {
				throw new EditEntityException( 'There is no entity2', 'wikibase-no-entity' );
			}
			$this->mApplicableRevisionId = $applicableRevisionId;
			$this->mApplicableDiff = $this->mBaseDiff->getApplicableDiff( $this->mApplicableEntity->toArray() );
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
	protected function applyPatch( $entity = null ) {
		$newEntity = isset( $entity ) ? $entity : clone $this->mApplicableEntity;

		if ( $newEntity === null ) {
			throw new EditEntityException( 'There is no entity to patch', 'wikibase-no-entity' );
		}
		$this->mApplicableDiff->apply( $newEntity );
		$this->mPatchedEntity = $newEntity;
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
	 * Get the entity for the base revision
	 *
	 * @since 0.1
	 *
	 * @return null|Entity the entity or null if not yet available
	 */
	public function getBaseEntity() {
		return $this->mBaseEntity;
	}

	/**
	 * Get the entity for the last revision
	 * 
	 * @since 0.1
	 * 
	 * @return null|Diff the entity or null if not yet available
	 */
	public function getApplicableEntity() {
		return $this->mApplicableEntity;
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
	 * Get the patched entity for the new version
	 * 
	 * @since 0.1
	 * 
	 * @return null|Diff the entity or null if not yet available
	 */
	public function getPatchedEntity() {
		return $this->mPatchedEntity;
	}

	public function isComplete() {
		return $this->mBaseDiff->getAddedValues();
	}

	/**
	 * Check if no edits were made by other users since the given revision. Limit to 50 revisions for the
	 * sake of performance.
	 *
	 * Note that this makes the assumption that revision ids are monotonically increasing.
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
}

class EditEntityException extends \UsageException {}
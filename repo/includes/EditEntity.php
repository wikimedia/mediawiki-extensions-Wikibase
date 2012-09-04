<?php

namespace Wikibase;

use Status, Revision, User, WikiPage, Title;

/**
 * Handler for editing activity, providing a unified interface for saving modified entities while performing
 * permission checks and handling edit conflicts.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 */
class EditEntity {

	/**
	 * The original entity we use for creating the diff
	 * @since 0.1
	 * @var EntityContent
	 */
	protected $newContent = null;

	/**
	 * @since 0.1
	 * @var Revision
	 */
	protected $baseRev = null;

	/**
	 * @since 0.1
	 * @var int
	 */
	protected $baseRevId = null;

	/**
	 * @since 0.1
	 * @var Revision
	 */
	protected $currentRev = null;

	/**
	 * @since 0.1
	 * @var int
	 */
	protected $currentRevId = null;

	/**
	 * @since 0.1
	 * @var WikiPage
	 */
	protected $page = null;

	/**
	 * @since 0.1
	 * @var Status
	 */
	protected $status = null;

	/**
	 * @since 0.1
	 * @var User
	 */
	protected $user = null;

	/**
	 * @since 0.1
	 * @var array
	 */
	protected $requiredPremissions = array(
		'edit',
	);

	/**
	 * Constructs a new EditEntity
	 *
	 * @since 0.1
	 *
	 * @param EntityContent $newContent the new entity content
	 * @param User|null     $user       the user performing the edit (defaults to $wgUser)
	 * @param int|null      $baseRevId  the base revision ID for conflict checking. Defaults to the current revision.
	 */
	public function __construct( EntityContent $newContent, \User $user = null, $baseRevId = null) {
		$this->newContent = $newContent;

		$this->user = $user;
		$this->baseRevId = $baseRevId;
	}

	/**
	 * Returns the new entity content to be saved. May be different from the content supplied to the constructor in
	 * case the content was patched to resolve edit conflicts.
	 *
	 * @return EntityContent
	 */
	public function getNewContent() {
		return $this->newContent;
	}

	/**
	 * Returns the current revision of the entity's page.
	 * Shorthand for $this->getPage()->getRevision().
	 *
	 * @return Revision
	 */
	public function getCurrentRevision() {
		if ( $this->isNew() ) {
			return null;
		}

		if ( $this->currentRev === null ) {
			//NOTE: it's important to remember this, if someone calls clear() on $this->getPage(), this should NOT change!
			$this->currentRev = $this->getPage()->getRevision();
		}

		return $this->currentRev;
	}


	/**
	 * Returns the current content of the entity's page.
	 * Shorthand for $this->getPage()->getContent().
	 *
	 * @return EntityContent
	 */
	public function getCurrentContent() {
		if ( $this->isNew() ) {
			return null;
		}

		return $this->getPage()->getContent();
	}

	/**
	 * Returns the user who performs the edit.
	 *
	 * @return User
	 */
	public function getUser() {
		global $wgUser;

		if ( $this->user === null ) {
			$this->user = $wgUser;
		}

		return $this->user;
	}

	/**
	 * Returns the WikiPage to be edited.
	 * Shorthand for $this->getNewContent()->getWikiPage().
	 *
	 * @return WikiPage
	 */
	public function getPage() {
		if ( $this->isNew() ) {
			return null;
		}

		if ( $this->page === null ) {
			$this->page = $this->getNewContent()->getWikiPage();
		}

		return $this->page;
	}

	/**
	 * Returns the Title of the page to be edited.
	 * Shorthand for $this->getPage()->getTitle().
	 *
	 * @return Title
	 */
	public function getTitle() {
		if ( $this->isNew() ) {
			return null;
		}

		return $this->newContent->getTitle();
	}

	/**
	 * Returns whether the new content is new, that is, does not have an ID yet and thus no title, page or revisions.
	 */
	public function isNew() {
		return $this->newContent->isNew();
	}

	/**
	 * Returns the current revision ID.
	 * Shorthand for $this->getPage()->getLatest().
	 *
	 * @return int
	 */
	public function getCurrentRevisionId() {
		if ( $this->isNew() ) {
			return 0;
		}

		if ( $this->currentRevId === null ) {
			//NOTE: it's important to remember this, if someone calls clear() on $this->getPage(), this should NOT change!
			$this->currentRevId = $this->getPage()->getLatest();
		}

		return $this->currentRevId;
	}

	/**
	 * Returns the base revision ID.
	 * In the trivial non-conflicting case, this will be the same as $this->getCurrentRevisionId().
	 *
	 * @return int
	 */
	public function getBaseRevisionId() {
		if ( $this->baseRevId === null ) {
			$this->baseRevId = $this->getCurrentRevisionId();
		}

		return $this->baseRevId;
	}

	/**
	 * Returns the edits base revision.
	 * In the trivial non-conflicting case, this will be the same as $this->getCurrentRevision().
	 *
	 * @throws \MWException
	 * @return Revision
	 */
	public function getBaseRevision() {
		if ( $this->baseRev === null ) {
			$id = $this->getBaseRevisionId();

			if ( $id === $this->getCurrentRevisionId() ) {
				$this->baseRev = $this->getCurrentRevision();
			} else {
				$this->baseRev = Revision::newFromId( $id );
				if ( $this->baseRev === false ) {
					throw new \MWException( 'base revision ID: ' . $id );
				}
			}
		}

		return $this->baseRev;
	}

	/**
	 * Returns the content of the base revision.
	 * Shorthand for $this->getBaseRevision()->getContent()
	 *
	 * @return EntityContent
	 */
	public function getBaseContent() {
		$rev = $this->getBaseRevision();
		return $rev == null ? null : $rev->getContent();
	}

	/**
	 * Determines whether an edit conflict exists, that is, whether another user has edited the same item
	 * after the base revision was created.
	 *
	 * @param \Status $status An status object to update with any warnings. Note that the edit conflict as such
	 *        will *not* be reported in the status object, since it might be fixable.
	 *
	 * @return bool
	 */
	public function hasEditConflict( Status $status ) {
		if ( $this->isNew() ) {
			return false;
		}

		if ( $this->getBaseRevisionId() == $this->getCurrentRevisionId() ) {
			return false;
		}

		if ( self::userWasLastToEdit( $this->getUser()->getId(), $this->getBaseRevisionId() ) ) {
			$status->warning( 'wikibase-self-conflict' );
			return false;
		}

		return true;
	}

	/**
	 * Get the status object. Only defined after attemptSave() was called.
	 *
	 * @since 0.1
	 *
	 * @return null|Status
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * Attempts to patch an edit conflict by patching the difference between the base content and the new content into
	 * the current content. Will fail if any conflicts are detected.
	 *
	 * The behaviour of this function is undefined if hasEditConflict() returns false.
	 *
	 * @param Status $status a status object to report details to. A successful patch fill add a warning.
	 *        A failed patch will add a fatal error.
	 *
	 * @todo: implement!
	 *
	 * @return bool True if the conflict could be resolved, false otherwise
	 */
	public function fixEditConflict( Status $status ) {
		$status->error( 'edit-conflict' );
		$status->setResult( false );
		return false;
	}

	/**
	 * Adds another permission (action) to be checked by checkEditPermissions().
	 * Per default, the 'edit' permission (and if needed, the 'create' permission) is checked.
	 *
	 * @param String $permission
	 */
	public function addRequiredPermission( $permission ) {
		$this->requiredPremissions[] = $permission;
	}

	/**
	 * Checks the necessary permissions to perform this edit.
	 * Per default, the 'edit' permission (and if needed, the 'create' permission) is checked.
	 * Use addRequiredPermission() to check more permissions.
	 *
	 * @throws \PermissionsError if the user's permissions are not sufficient
	 */
	public function checkEditPermissions() {
		foreach ( $this->requiredPremissions as $action ) {
			$status = $this->newContent->checkPermission( $action, $this->getUser() );

			if ( !$status->isOK() ) {
				throw new \PermissionsError( $action, $status->getErrorsArray() );
			}
		}
	}

	/**
	 * Attempts to save the new entity content, chile first checking for permissions, edit conflicts, etc.
	 *
	 * @param String $summary the edit summary
	 * @param int $flags      the edit flags (see WikiPage::toEditContent)
	 *
	 * @return Status Indicates success and provides detailed warnings or error messages.
	 * @throws \MWException
	 *
	 * @see WikiPage::toEditContent
	 */
	public function attemptSave( $summary, $flags = 0 ) {
		$this->checkEditPermissions();

		$this->status = Status::newGood();

		if ( !$this->status->isOK() ) {
			return $this->status;
		}

		//NOTE: Make sure the current revision is loaded and cached.
		//      Would happen on demand anyway, but we want a well-defined point at which "current" is frozen
		//      to a specific revision, just before the first check for edit conflicts.
		$this->getCurrentRevision();
		$this->getCurrentRevisionId();

		$conflict = $this->hasEditConflict( $this->status );

		if ( $conflict ) {
			$fixed = $this->fixEditConflict( $this->status );

			if ( $fixed ) {
				$conflict = false;
			}
		}

		if ( $conflict ) {
			$this->status->error( 'edit-conflict' );
			$this->status->setResult( false );
		}

		if ( !$this->status->isOK() ) {
			return $this->status;
		}

		$editStatus = $this->newContent->save(
			$summary,
			$this->getUser(),
			$flags | EDIT_AUTOSUMMARY,
			$this->getCurrentRevisionId(),
			$this
		);

		$this->status->merge( $editStatus );
		return $this->status;
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
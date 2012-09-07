<?php

namespace Wikibase;

use Status, Revision, User, WikiPage, Title, WebRequest, OutputPage;

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
	 * The modified entity we are trying to save
	 *
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
	 * Bit field for error types, using the EditEntity::XXX_ERROR constants
	 *
	 * @since 0.1
	 * @var int
	 */
	protected $errorType = 0;

	/**
	 * indicates a permission error
	 */
	const PERMISSION_ERROR = 1;

	/**
	 * indicates an unresolved edit conflict
	 */
	const EDIT_CONFLICT_ERROR = 2;

	/**
	 * indicates a token or session error
	 */
	const TOKEN_ERROR = 4;

	/**
	 * indicates that an error occurred while saving
	 */
	const SAVE_ERROR = 8;

	/**
	 * bit mask for asking for any error.
	 */
	const ANY_ERROR = 0xFFFFFFFF;

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
	 * @param int|boolean   $baseRevId  the base revision ID for conflict checking.
	 *                                  Defaults to false, disabling conflict checks.
	 *                                  `true` can be used to set the base revision to the current revision:
	 *                                  This will detect "late" edit conflicts, i.e. someone squeezing in an edit
	 *                                  just before the actual database transaction for saving beings.
	 *                                  The empty string and 0 are both treated as `false`, disabling conflict checks.
	 */
	public function __construct( EntityContent $newContent, \User $user = null, $baseRevId = false ) {
		$this->newContent = $newContent;

		if ( is_string( $baseRevId ) ) {
			$baseRevId = intval( $baseRevId );
		}

		if ( $baseRevId === '' || $baseRevId === 0 ) {
			$baseRevId = false;
		}

		$this->user = $user;
		$this->baseRevId = $baseRevId;

		$this->errorType = 0;
		$this->status = Status::newGood();
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
	 * If no base revision was supplied to the constructor, this will return false.
	 * In the trivial non-conflicting case, this will be the same as $this->getCurrentRevisionId().
	 *
	 * @return int|boolean
	 */
	public function getBaseRevisionId() {
		if ( $this->baseRevId === null || $this->baseRevId === true ) {
			$this->baseRevId = $this->getCurrentRevisionId();
		}

		return $this->baseRevId;
	}

	/**
	 * Returns the edits base revision.
	 * If no base revision was supplied to the constructor, this will return null.
	 * In the trivial non-conflicting case, this will be the same as $this->getCurrentRevision().
	 *
	 * @throws \MWException
	 * @return Revision
	 */
	public function getBaseRevision() {
		if ( $this->baseRev === null ) {
			$id = $this->getBaseRevisionId();

			if ( $id === false ) {
				return null;
			} else if ( $id === $this->getCurrentRevisionId() ) {
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
	 * If no base revision was supplied to the constructor, this will return null.
	 * Shorthand for $this->getBaseRevision()->getContent()
	 *
	 * @return EntityContent
	 */
	public function getBaseContent() {
		$rev = $this->getBaseRevision();
		return $rev == null ? null : $rev->getContent();
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
	 * Determines whether the last call to attemptSave was successful.
	 *
	 * @since 0.1
	 *
	 * @return bool false if attemptSave() failed, true otherwise
	 */
	public function isSuccess() {
		if ( $this->errorType > 0 ) {
			return false;
		}

		return $this->status->isOK();
	}

	/**
	 * Checks whether this EditEntity encountered any of the given error types while executing attemptSave().
	 *
	 * @since 0.1
	 *
	 * @param int $errorType bit field using the EditEntity::XXX_ERROR constants.
	 *            Defaults to EditEntity::ANY_ERROR.
	 *
	 * @return boolean true if this EditEntity encountered any of the error types in $errorType, false otherwise.
	 */
	public function hasError( $errorType = self::ANY_ERROR ) {
		return ( $this->errorType & $errorType ) > 0;
	}

	/**
	 * Determines whether an edit conflict exists, that is, whether another user has edited the same item
	 * after the base revision was created.
	 *
	 * @return bool
	 */
	public function hasEditConflict() {
		if ( $this->isNew() || !$this->doesCheckForEditConflicts() ) {
			return false;
		}

		if ( $this->getBaseRevisionId() == $this->getCurrentRevisionId() ) {
			return false;
		}

		if ( self::userWasLastToEdit( $this->getUser()->getId(), $this->getBaseRevisionId() ) ) {
			$this->status->warning( 'wikibase-self-conflict' );
			return false;
		}

		if ( $this->fixEditConflict() ) {
			return false;
		}

		$this->status->fatal( 'edit-conflict' );
		$this->errorType |= self::EDIT_CONFLICT_ERROR;

		return true;
	}

	/**
	 * Attempts to fix an edit conflict by patching the intended change into the current revision after
	 * checking for conflicts.
	 *
	 * @return bool True if the conflict could be resolved, false otherwise
	 */
	protected function fixEditConflict() {
		//@todo: implement this!
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
			$permissionStatus = $this->newContent->checkPermission( $action, $this->getUser() );

			$this->status->merge( $permissionStatus );

			if ( !$this->status->isOK() ) {
				$this->errorType |= self::PERMISSION_ERROR;
				throw new \PermissionsError( $action, $permissionStatus->getErrorsArray() );
			}
		}
	}

	/**
	 * Make sure the given WebRequest contains a valid edit token.
	 *
	 * @param String $token the token to check
	 *
	 * @return bool true if the token is valid
	 */
	public function isTokenOK( $token ) {
		$tokenOk = $this->getUser()->matchEditToken( $token );
		$tokenOkExceptSuffix = $this->getUser()->matchEditTokenNoSuffix( $token );

		if ( !$tokenOk ) {
			if ( $tokenOkExceptSuffix ) {
				$this->status->fatal( 'wikibase-undo-revision-error', 'token_suffix_mismatch' );
			} else {
				$this->status->fatal( 'wikibase-undo-revision-error', 'session_fail_preview' );
			}

			$this->errorType |= self::TOKEN_ERROR;
			return false;
		}

		return true;
	}

	/**
	 * Attempts to save the new entity content, chile first checking for permissions, edit conflicts, etc.
	 *
	 * @param String $summary    the edit summary
	 * @param int    $flags      the edit flags (see WikiPage::toEditContent)
	 * @param String|null|bool   $token Edit token to check, or false to disable the token check. False per default.
	 *                           Null will fail the token text, as will the empty string.
	 *
	 * @return Status Indicates success and provides detailed warnings or error messages.
	 * @see      WikiPage::toEditContent
	 */
	public function attemptSave( $summary, $flags = 0, $token = false ) {
		$this->status = Status::newGood();
		$this->errorType = 0;

		$this->checkEditPermissions();

		if ( $token !== false && !$this->isTokenOK( $token ) ) {
			return $this->status;
		}

		//NOTE: Make sure the current revision is loaded and cached.
		//      Would happen on demand anyway, but we want a well-defined point at which "current" is frozen
		//      to a specific revision, just before the first check for edit conflicts.
		$this->getCurrentRevision();
		$this->getCurrentRevisionId();

		if ( $this->hasEditConflict() ) {
			return $this->status;
		}

		$editStatus = $this->newContent->save(
			$summary,
			$this->getUser(),
			$flags | EDIT_AUTOSUMMARY,
			$this->getCurrentRevisionId(), // note: this should be the parent revision, not the true base revision!
			$this->doesCheckForEditConflicts() ? $this : null
		);

		if ( !$editStatus->isOK() ) {
			$this->errorType |= self::SAVE_ERROR;
		}

		$this->status->merge( $editStatus );
		return $this->status;
	}

	/**
	 * Whether this EditEntity will check for edit conflicts
	 *
	 * @return bool
	 */
	public function doesCheckForEditConflicts() {
		return $this->getBaseRevisionId() !== false;
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
	 * @param int|bool $lastRevId the revision the user supplied (or false)
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

	/**
	 * Shows an error page showing the errors that occurred during attemptSave(), if any.
	 *
	 * @param OutputPage $out the output object to write output to
	 * @param String|null $titleMessage message key for the page title
	 *
	 * @return bool true if an error page was shown, false if there were no errors to show.
	 */
	public function showErrorPage( OutputPage $out = null, $titleMessage = null ) {
		global $wgOut;

		if ( $out === null ) {
			$out = $wgOut;
		}

		if ( $this->status === null || $this->status->isOK() ) {
			return false;
		}

		if ( $titleMessage === null ) {
			$out->prepareErrorPage( wfMessage( 'errorpagetitle' ) );
		} else {
			$out->prepareErrorPage( wfMessage( $titleMessage ), wfMessage( 'errorpagetitle' ) );
		}

		$this->showStatus( $out );

		$out->returnToMain();

		return true;
	}

	/**
	 * Shows any errors or warnings from attemptSave().
	 *
	 * @param OutputPage $out the output object to write output to
	 *
	 * @return bool true if any message was shown, false if there were no errors to show.
	 */
	public function showStatus( OutputPage $out = null ) {
		global $wgOut;

		if ( $out === null ) {
			$out = $wgOut;
		}

		if ( $this->status === null || $this->status->isGood() ) {
			return false;
		}

		$text = $this->status->getMessage();
		$out->addWikiText( $text );

		return true;
	}

	/**
	 * Die with an error corresponding to any errors that occurred during attemptSave(), if any.
	 * Intended for use in API modules.
	 *
	 * If there is no error but there are warnings, they are added to the API module's result.
	 *
	 * @param \ApiBase $api          the API module to report the error for.
	 * @param String   $errorCode    string Brief, arbitrary, stable string to allow easy
	 *                               automated identification of the error, e.g., 'unknown_action'
	 * @param int      $httpRespCode int HTTP response code
	 * @param array    $extradata    array Data to add to the "<error>" element; array in ApiResult format
	 */
	public function reportApiErrors( \ApiBase $api, $errorCode, $httpRespCode = 0, $extradata = null ) {
		if ( $this->status === null ) {
			return;
		}

		// report all warnings
		// XXX: also report all errors, in sequence, here, before failing on the error?
		$errors = $this->status->getErrorsByType( 'warning' );
		if ( is_array($errors) && $errors !== array() ) {
			$path = array( null, 'warnings' );
			$api->getResult()->addValue( null, 'warnings', $errors );
			$api->getResult()->setIndexedTagName( $path, 'warning' );
		}

		if ( !$this->status->isOK() ) {
			$description = $this->status->getWikiText( 'wikibase-api-cant-edit', 'wikibase-api-cant-edit' );
			$api->dieUsage( $description, $errorCode, $httpRespCode, $extradata );
		}
	}
}

<?php

namespace Wikibase;

use Status, Revision, User, WikiPage, Title, WebRequest, OutputPage;

/**
 * Handler for editing activity, providing a unified interface for saving modified entities while performing
 * permission checks and handling edit conflicts.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseRepo
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
	 * Indicates that the content failed some precondition to saving,
	 * such a a global uniqueness constraint.
	 */
	const PRECONDITION_FAILED = 16;

	/**
	 * Indicates that the content triggered an edit filter that uses
	 * the EditFilterMergedContent hook to supervise edits.
	 */
	const FILTERED = 32;

	/**
	 * Indicates that the edit exceeded a rate limit.
	 */
	const RATE_LIMIT = 64;

	/**
	 * bit mask for asking for any error.
	 */
	const ANY_ERROR = 0xFFFFFFFF;

	/**
	 * @since 0.1
	 * @var array
	 */
	protected $requiredPermissions = array(
		'edit',
	);

	/**
	 * Constructs a new EditEntity
	 *
	 * @since 0.1
	 *
	 * @param EntityContent   $newContent the new entity content
	 * @param User|null       $user       the user performing the edit (defaults to $wgUser)
	 * @param int|boolean     $baseRevId  the base revision ID for conflict checking.
	 *                                    Defaults to false, disabling conflict checks.
	 *                                  `true` can be used to set the base revision to the current revision:
	 *                                    This will detect "late" edit conflicts, i.e. someone squeezing in an edit
	 *                                    just before the actual database transaction for saving beings.
	 *                                    The empty string and 0 are both treated as `false`, disabling conflict checks.
	 * @param \IContextSource $context the context source to use while processing the edit; defaults
	 *                        to \RequestContext::getMain().
	 */
	public function __construct( EntityContent $newContent, \User $user = null, $baseRevId = false,
			\IContextSource $context = null ) {

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

		if ( $context === null ) {
			$context = \RequestContext::getMain();
		}

		$this->context = $context;
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
	 * @return Title|bool
	 */
	public function getTitle() {
		if ( $this->isNew() ) {
			return false;
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
	 * @return Revision|null
	 * @throws \MWException
	 */
	public function getBaseRevision() {
		wfProfileIn( __METHOD__ );

		if ( $this->baseRev === null ) {
			$id = $this->getBaseRevisionId();

			if ( $id === false ) {
				return null;
			} else if ( $id === $this->getCurrentRevisionId() ) {
				$this->baseRev = $this->getCurrentRevision();
			} else {
				$this->baseRev = Revision::newFromId( $id );
				if ( $this->baseRev === false ) {
					wfProfileOut( __METHOD__ );
					throw new \MWException( 'base revision ID: ' . $id );
				}
			}
		}

		wfProfileOut( __METHOD__ );
		return $this->baseRev;
	}

	/**
	 * Returns the content of the base revision.
	 * If no base revision was supplied to the constructor, this will return null.
	 * Shorthand for $this->getBaseRevision()->getContent()
	 *
	 * @return EntityContent|null
	 */
	public function getBaseContent() {
		$rev = $this->getBaseRevision();
		return $rev == null ? null : $rev->getContent();
	}

	/**
	 * Get the status object. Only defined after attemptSave() was called.
	 *
	 * After a successful save, the Status object's value field will contain an array,
	 * just like the status returned by WikiPage::doEditContent(). Well known fields
	 * in the status value are:
	 *
	 *  - new: bool whether the edit created a new page
	 *  - revision: Revision the new revision object
	 *  - errorFlags: bit field indicating errors, see the XXX_ERROR constants.
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
	 * Returns the Revision created by attemptSave(), if it was successful.
	 * If attemptSave() has not yet been called or failed, null is returned.
	 *
	 * @since 0.3
	 *
	 * @return Revision|null
	 */
	public function getNewRevision() {
		if ( $this->errorType > 0 || !$this->status || !$this->status->isOK() ) {
			return null;
		}

		$value = $this->status->getValue();
		return isset( $value['revision'] ) ? $value['revision'] : null;
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
	 * Returns a bitfield indicating errors encountered while saving.
	 *
	 * @since 0.4
	 *
	 * @return int $errorType bit field using the EditEntity::XXX_ERROR constants.
	 */
	public function getErrors( ) {
		return $this->errorType;
	}

	/**
	 * Determines whether an edit conflict exists, that is, whether another user has edited the same item
	 * after the base revision was created.
	 *
	 * @return bool
	 */
	public function hasEditConflict() {
		wfProfileIn( __METHOD__ );

		if ( $this->isNew() || !$this->doesCheckForEditConflicts() ) {
			wfProfileOut( __METHOD__ );
			return false;
		}

		if ( $this->getBaseRevisionId() == $this->getCurrentRevisionId() ) {
			wfProfileOut( __METHOD__ );
			return false;
		}

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * Attempts to fix an edit conflict by patching the intended change into the current revision after
	 * checking for conflicts. This modifies $this->newContent but does not write anything to the
	 * database. Saving of the new content may still fail.
	 *
	 * @return bool True if the conflict could be resolved, false otherwise
	 */
	public function fixEditConflict() {
		$base = $this->getBaseContent();
		$current = $this->getCurrentContent();
		$new = $this->getNewContent();

		// calculate patch against base revision
		//FIXME: can $current or $base be null?!
		$patch = $base->getEntity()->getDiff( $new->getEntity() ); // diff from base to new

		if ( $patch->isEmpty() ) {
			// we didn't technically fix anything, but if there is nothing to change,
			// the edit will apply cleanly.
			return true;
		}

		// apply the patch( base -> new ) to the current revision.
		$patchedCurrent = $current->getEntity()->copy();
		$patchedCurrent->patch( $patch );

		// detect conflicts against current revision
		$cleanPatch = $current->getEntity()->getDiff( $patchedCurrent );

		$conflicts = $patch->count() - $cleanPatch->count();

		if ( $conflicts > 0 ) {
			// patch doesn't apply cleanly
			if ( self::userWasLastToEdit( $this->getUser()->getId(), $this->getBaseRevisionId() ) ) {
				// it's a self-conflict
				if ( $cleanPatch->count() === 0 ) {
					// patch collapsed, possibly because of diff operation change from base to current
					return false;
				}
				else {
					// we still have a working patch, try to apply
					$this->status->warning( 'wikibase-self-conflict-patched' );
				}
			} else {
				// there are unresolvable conflicts.
				return false;
			}
		} else {
			// can apply cleanly

			$this->status->warning( 'wikibase-conflict-patched' );
		}

		// create a new entity and tuck it away
		$this->newContent = EntityContentFactory::singleton()->newFromEntity( $patchedCurrent );

		return true;
	}

	/**
	 * Adds another permission (action) to be checked by checkEditPermissions().
	 * Per default, the 'edit' permission (and if needed, the 'create' permission) is checked.
	 *
	 * @param String $permission
	 */
	public function addRequiredPermission( $permission ) {
		$this->requiredPermissions[] = $permission;
	}

	/**
	 * Checks the necessary permissions to perform this edit.
	 * Per default, the 'edit' permission (and if needed, the 'create' permission) is checked.
	 * Use addRequiredPermission() to check more permissions.
	 */
	public function checkEditPermissions() {
		wfProfileIn( __METHOD__ );

		foreach ( $this->requiredPermissions as $action ) {
			$permissionStatus = $this->newContent->checkPermission( $action, $this->getUser() );

			$this->status->merge( $permissionStatus );

			if ( !$this->status->isOK() ) {
				$this->errorType |= self::PERMISSION_ERROR;
				$this->status->fatal( 'no-permission' );
			}
		}

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Checks if rate limits have been exceeded.
	 */
	public function checkRateLimits() {
		wfProfileIn( __METHOD__ );

		$exceeded = false;

		if ( $this->getUser()->pingLimiter( 'edit' ) ) {
			$exceeded = true;
		} else if ( $this->isNew() && $this->getUser()->pingLimiter( 'create' ) ) {
			$exceeded = true;
		}

		if ( $exceeded ) {
			$this->errorType |= self::RATE_LIMIT;
			$this->status->fatal( 'actionthrottledtext' );
		}

		wfProfileOut( __METHOD__ );
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
				$this->status->fatal( 'token_suffix_mismatch' );
			} else {
				$this->status->fatal( 'session_fail_preview' );
			}

			$this->errorType |= self::TOKEN_ERROR;
			return false;
		}

		return true;
	}

	/**
	 * Attempts to save the new entity content, chile first checking for permissions, edit conflicts, etc.
	 *
	 * @param String      $summary    The edit summary
	 * @param int         $flags      The edit flags (see WikiPage::toEditContent)
	 * @param String|bool $token      Edit token to check, or false to disable the token check.
	 *                                Null will fail the token text, as will the empty string.
	 * @param bool|null $watch        Whether the user wants to watch the entity.
	 *                                Set to null to apply default according to getWatchDefault().
	 *
	 * @throws \ReadOnlyError
	 * @return Status Indicates success and provides detailed warnings or error messages. See
	 *         getStatus() for more details.
	 * @see    WikiPage::doEditContent
	 */
	public function attemptSave( $summary, $flags, $token, $watch = null ) {
		wfProfileIn( __METHOD__ );

		if ( wfReadOnly() ) {
			throw new \ReadOnlyError();
		}

		if ( $watch === null ) {
			$watch = $this->getWatchDefault();
		}

		$this->status = Status::newGood();
		$this->errorType = 0;

		if ( $token !== false && !$this->isTokenOK( $token ) ) {
			//@todo: This is redundant to the error code set in isTokenOK().
			//       We should figure out which error codes the callers expect,
			//       and only set the correct error code, in one place, probably here.
			$this->errorType |= self::TOKEN_ERROR;
			$this->status->fatal( 'sessionfailure' );
			$this->status->setResult( false, array( 'errorFlags' => $this->errorType ) );

			wfProfileOut( __METHOD__ );
			return $this->status;
		}

		$this->checkEditPermissions();

		$this->checkRateLimits(); // modifies $this->status

		if ( !$this->status->isOK() ) {
			$this->status->setResult( false, array( 'errorFlags' => $this->errorType ) );

			wfProfileOut( __METHOD__ );
			return $this->status;
		}

		//NOTE: Make sure the current revision is loaded and cached.
		//      Would happen on demand anyway, but we want a well-defined point at which "current" is frozen
		//      to a specific revision, just before the first check for edit conflicts.
		$this->getCurrentRevision();
		$this->getCurrentRevisionId();

		$this->applyPreSaveChecks(); // modifies $this->status

		if ( !$this->status->isOK() ) {
			$this->errorType |= self::PRECONDITION_FAILED;
		}

		if ( !$this->status->isOK() ) {
			$this->status->setResult( false, array( 'errorFlags' => $this->errorType ) );

			wfProfileOut( __METHOD__ );
			return $this->status;
		}

		if ( !$this->isNew() ) {
			$context = clone $this->context;

			$context->setTitle( $this->getTitle() );
			$context->setWikiPage( $this->getPage() );
		} else {
			$context = $this->context;
		}

		// Run edit filter hooks
		$filterStatus = Status::newGood();
		if ( !wfRunHooks( 'EditFilterMergedContent',
			array( $context, $this->newContent, &$filterStatus, $summary, $this->getUser(), false ) ) ) {

			# Error messages etc. were handled inside the hook.
			$filterStatus->setResult( false, $filterStatus->getValue() );
		}

		if ( !$filterStatus->isOK() ) {
			$this->errorType |= self::FILTERED;
		}

		$this->status->merge( $filterStatus );

		if ( !$this->status->isOK() ) {
			$this->status->setResult( false, array( 'errorFlags' => $this->errorType ) );

			wfProfileOut( __METHOD__ );
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

		$this->status->setResult( $editStatus->isOK(), $editStatus->getValue() );
		$this->status->merge( $editStatus );

		if ( $this->status->isOK() ) {
			$this->updateWatchlist( $watch );
		} else {
			$value = $this->status->getValue();
			$value['errorFlags'] = $this->errorType;
			$this->status->setResult( false, $value );
		}

		wfProfileOut( __METHOD__ );
		return $this->status;
	}

	protected function applyPreSaveChecks() {
		if ( $this->hasEditConflict() ) {
			if ( !$this->fixEditConflict() ) {
				$this->status->fatal( 'edit-conflict' );
				$this->errorType |= self::EDIT_CONFLICT_ERROR;

				wfProfileOut( "Wikibase-" . __METHOD__ );
				return $this->status;
			}
		}

		/**
		 * @var Entity $entity
		 */
		$entity = $this->newContent->getEntity();

		// TODO: the below logic is Item specific, so would be good if this got handled
		// using composition or polymorphism.

		if ( $entity->getType() !== Item::ENTITY_TYPE ) {
			return $this->status;
		}

		$itemDiff = null;

		if ( $this->getBaseContent() instanceof EntityContent ) {
			//XXX: havn't we calculated this diff already?
			$itemDiff = $entity->getDiff( $this->getBaseContent()->getEntity() );
		}
		//XXX: ...else diff against an empty item?...

		$multilangViolationDetector = new MultiLangConstraintDetector();
		$multilangViolationDetector->addConstraintChecks(
			$entity,
			$this->status,
			$itemDiff
		);

		if ( !$this->status->isOk() ) {
			return $this->status;
		}

		$dbw = wfGetDB( DB_MASTER );

		// Do not run this when running test using MySQL as self joins fail on temporary tables.
		if ( !defined( 'MW_PHPUNIT_TEST' )
			|| !( StoreFactory::getStore() instanceof \Wikibase\SqlStore )
			|| $dbw->getType() !== 'mysql' ) {

			// The below looks for all conflicts and then removes the ones not
			// caused by the edit. This can be improved by only looking for
			// those conflicts that can be caused by the edit.

			$termViolationDetector = new LabelDescriptionDuplicateDetector();

			$termViolationDetector->addLabelDescriptionConflicts(
				$entity,
				$this->status,
				StoreFactory::getStore()->getTermIndex(),
				$itemDiff === null ? null : $itemDiff->getLabelsDiff(),
				$itemDiff === null ? null : $itemDiff->getDescriptionsDiff()
			);
		}

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
	 * @param int|bool $userId the users numeric identifier
	 * @param int|bool $lastRevId the revision the user supplied (or false)
	 *
	 * @return bool
	 */
	public static function userWasLastToEdit( $userId = false, $lastRevId = false ) {
		wfProfileIn( __METHOD__ );

		// If the lastRevId is missing then skip all further test and give false.
		// Note that without a revision id it will not be possible to do patching.
		if ( $lastRevId === false ) {
			wfProfileOut( __METHOD__ );
			return false;
		}
		else {
			$revision = \Revision::newFromId( $lastRevId );
			if ( !isset( $revision ) ) {
				wfProfileOut( __METHOD__ );
				return false;
			}
		}

		// If the userId is missing then skip all further test and give false.
		// It is only the user id that is used later on.
		if ( $userId === false ) {
			wfProfileOut( __METHOD__ );
			return false;
		}
		else {
			$user = \User::newFromId( $userId );
			if ( !isset( $user ) ) {
				wfProfileOut( __METHOD__ );
				return false;
			}
		}

		// If the title is missing then skip all further test and give false.
		// There must be a title so we can get an article id
		$title = $revision->getTitle();
		if ( $title === null ) {
			wfProfileOut( __METHOD__ );
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
			array( 'ORDER BY' => 'rev_timestamp ASC', 'LIMIT' => 1 )
		);
		wfProfileOut( __METHOD__ );
		return $res->current() === false; // return true if query had no match
	}

	/**
	 * Shows an error page showing the errors that occurred during attemptSave(), if any.
	 *
	 * If $titleMessage is set it is made an assumption that the page is still the original
	 * one, and there should be no link back from a special error page.
	 *
	 * @param String|null $titleMessage message key for the page title
	 *
	 * @return bool true if an error page was shown, false if there were no errors to show.
	 */
	public function showErrorPage( $titleMessage = null ) {
		wfProfileIn( __METHOD__ );
		$out = $this->context->getOutput();

		if ( $this->status === null || $this->status->isOK() ) {
			wfProfileOut( __METHOD__ );
			return false;
		}

		if ( $titleMessage === null ) {
			$out->prepareErrorPage( wfMessage( 'errorpagetitle' ) );
		} else {
			$out->prepareErrorPage( wfMessage( $titleMessage ), wfMessage( 'errorpagetitle' ) );
		}

		$this->showStatus();

		if ( !isset( $titleMessage ) ) {
			$out->returnToMain( '', $this->getTitle() );
		}

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * Shows any errors or warnings from attemptSave().
	 *
	 * @return bool true if any message was shown, false if there were no errors to show.
	 */
	protected function showStatus( ) {
		wfProfileIn( __METHOD__ );
		$out = $this->context->getOutput();

		if ( $this->status === null || $this->status->isGood() ) {
			wfProfileOut( __METHOD__ );
			return false;
		}

		$text = $this->status->getMessage();

		$out->addHTML( \Html::element( 'div', array( 'class' => 'error' ), $text ) );

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * Returns whether the present edit would, per default,
	 * lead to the user watching the page.
	 *
	 * This uses the user's watchdefault and watchcreations settings
	 * and considers whether the entity is already watched by the user.
	 *
	 * @return bool
	 *
	 * @note: keep in sync with logic in EditPage
	 */
	public function getWatchDefault() {
		$user = $this->getUser();
		$title = $this->getTitle();

		if ( $title && !$title->exists() ) {
			$title = false;
		}

		if ( $user->getOption( 'watchdefault' ) ) {
			// Watch all edits
			return true;
		} elseif ( $user->getOption( 'watchcreations' ) && !$title ) {
			// Watch creations
			return true;
		}

		// keep current state
		return $title !== false && $user->isWatched( $title );
	}

	/**
	 * Watches or unwatches the entity.
	 *
	 * @param bool $watch whether to watch or unwatch the page.
	 *
	 * @throws \MWException
	 * @return void : keep in sync with logic in EditPage
	 */
	public function updateWatchlist( $watch ) {
		$user = $this->getUser();
		$title = $this->getTitle();

		if ( !$title ) {
			throw new \MWException( "Title not yet known!" );
		}

		if ( $user->isLoggedIn() && $watch != $user->isWatched( $title ) ) {
			$fname = __METHOD__;

			// Do this in its own transaction to reduce contention...
			$dbw = wfGetDB( DB_MASTER );
			$dbw->onTransactionIdle( function() use ( $dbw, $title, $watch, $user, $fname ) {
				$dbw->begin( $fname );
				if ( $watch ) {
					\WatchAction::doWatch( $title, $user );
				} else {
					\WatchAction::doUnwatch( $title, $user );
				}
				$dbw->commit( $fname );
			} );
		}
	}
}

<?php

namespace Wikibase;

use DerivativeContext;
use Hooks;
use Html;
use InvalidArgumentException;
use MWException;
use ReadOnlyError;
use RequestContext;
use Status;
use Title;
use User;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\WikibaseRepo;
use WikiPage;

/**
 * Handler for editing activity, providing a unified interface for saving modified entities while performing
 * permission checks and handling edit conflicts.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 */
class EditEntity {

	/**
	 * @var EntityRevisionLookup
	 */
	protected $entityRevisionLookup;

	/**
	 * @var EntityTitleLookup
	 */
	protected $titleLookup;

	/**
	 * @var EntityStore
	 */
	protected $entityStore;

	/**
	 * The modified entity we are trying to save
	 *
	 * @var Entity
	 */
	protected $newEntity = null;

	/**
	 * @var EntityRevision
	 */
	protected $baseRev = null;

	/**
	 * @var int
	 */
	protected $baseRevId = null;

	/**
	 * @var EntityRevision
	 */
	protected $latestRev = null;

	/**
	 * @var int
	 */
	protected $latestRevId = null;

	/**
	 * @var Status
	 */
	protected $status = null;

	/**
	 * @var User
	 */
	protected $user = null;

	/**
	 * @var Title
	 */
	protected $title = null;

	/**
	 * @var IContextSource
	 */
	protected $context = null;

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
	 * @param EntityTitleLookup $titleLookup
	 * @param EntityRevisionLookup $entityLookup
	 * @param EntityStore $entityStore
	 * @param EntityPermissionChecker $permissionChecker
	 * @param Entity $newEntity the new entity object
	 * @param User $user the user performing the edit
	 * @param int|boolean $baseRevId the base revision ID for conflict checking.
	 *        Defaults to false, disabling conflict checks.
	 *        `true` can be used to set the base revision to the latest revision:
	 *        This will detect "late" edit conflicts, i.e. someone squeezing in an edit
	 *        just before the actual database transaction for saving beings.
	 *        The empty string and 0 are both treated as `false`, disabling conflict checks.
	 * @param RequestContext|DerivativeContext $context the context to use while processing
	 *        the edit; defaults to RequestContext::getMain().
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		EntityTitleLookup $titleLookup,
		EntityRevisionLookup $entityLookup,
		EntityStore $entityStore,
		EntityPermissionChecker $permissionChecker,
		Entity $newEntity,
		User $user,
		$baseRevId = false,
		$context = null
	) {
		$this->newEntity = $newEntity;

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

		if ( $context !== null && !$context instanceof RequestContext && !$context instanceof DerivativeContext ) {
			throw new InvalidArgumentException( '$context must be an instance of RequestContext'
				 . ' or DerivativeContext' );
		}

		if ( $context === null ) {
			$context = RequestContext::getMain();
		}

		$this->context = $context;

		$this->titleLookup = $titleLookup;
		$this->entityRevisionLookup = $entityLookup;
		$this->entityStore = $entityStore;
		$this->permissionChecker = $permissionChecker;
	}

	/**
	 * Returns the new entity object to be saved. May be different from the entity supplied
	 * to the constructor in case the entity was patched to resolve edit conflicts.
	 *
	 * @return Entity
	 */
	public function getNewEntity() {
		return $this->newEntity;
	}

	/**
	 * Returns the ID of the entity that is being edited.
	 *
	 * @return EntityId
	 */
	public function getEntityId() {
		return $this->getNewEntity()->getId();
	}

	/**
	 * Returns the Title of the page holding the entity that is being edited.
	 *
	 * @return Title|null
	 */
	public function getTitle() {
		if ( $this->isNew() ) {
			return null;
		}

		if ( $this->title === null ) {
			$this->title = $this->titleLookup->getTitleForId( $this->getEntityId() );
		}

		return $this->title;
	}

	/**
	 * Returns the latest revision of the entity.
	 *
	 * @return EntityRevision|null
	 */
	public function getLatestRevision() {
		if ( $this->isNew() ) {
			return null;
		}

		wfProfileIn( __METHOD__ );
		if ( $this->latestRev === null ) {
			//NOTE: it's important to remember this, if someone calls clear() on $this->getPage(), this should NOT change!
			$this->latestRev = $this->entityRevisionLookup->getEntityRevision( $this->getEntityId() );
		}

		wfProfileOut( __METHOD__ );
		return $this->latestRev;
	}

	/**
	 * Returns the latest revision ID.
	 *
	 * @return int
	 */
	public function getLatestRevisionId() {
		if ( $this->isNew() ) {
			return 0;
		}

		wfProfileIn( __METHOD__ );
		if ( $this->latestRevId === null ) {
			if ( $this->latestRev !== null ) {
				$this->latestRevId = $this->latestRev->getRevision();
			} else {
				$this->latestRevId = $this->entityRevisionLookup->getLatestRevisionId( $this->getEntityId() );
			}
		}

		wfProfileOut( __METHOD__ );
		return $this->latestRevId;
	}

	/**
	 * Returns the user who performs the edit.
	 *
	 * @return User
	 */
	public function getUser() {
		return $this->user;
	}

	/**
	 * Returns whether the new content is new, that is, does not have an ID yet and thus no title, page or revisions.
	 */
	public function isNew() {
		return $this->newEntity->getId() === null;
	}

	/**
	 * Returns the base revision ID.
	 * If no base revision was supplied to the constructor, this will return false.
	 * In the trivial non-conflicting case, this will be the same as $this->getLatestRevisionId().
	 *
	 * @return int|boolean
	 */
	public function getBaseRevisionId() {
		if ( $this->baseRevId === null || $this->baseRevId === true ) {
			$this->baseRevId = $this->getLatestRevisionId();
		}

		return $this->baseRevId;
	}

	/**
	 * Returns the edits base revision.
	 * If no base revision was supplied to the constructor, this will return null.
	 * In the trivial non-conflicting case, this will be the same as $this->getLatestRevision().
	 *
	 * @return EntityRevision|null
	 * @throws MWException
	 */
	public function getBaseRevision() {
		wfProfileIn( __METHOD__ );

		if ( $this->baseRev === null ) {
			$baseRevId = $this->getBaseRevisionId();

			if ( $baseRevId === false ) {
				wfProfileOut( __METHOD__ );
				return null;
			} else if ( $baseRevId === $this->getLatestRevisionId() ) {
				$this->baseRev = $this->getLatestRevision();
			} else {
				$entityId = $this->getEntityId();
				$this->baseRev = $this->entityRevisionLookup->getEntityRevision( $entityId, $baseRevId );

				if ( $this->baseRev === null ) {
					wfProfileOut( __METHOD__ );
					throw new MWException( 'Base revision ID not found: rev ' . $baseRevId
						. ' of ' . $entityId->getSerialization() );
				}
			}
		}

		wfProfileOut( __METHOD__ );
		return $this->baseRev;
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
	 * Returns the revision created by attemptSave(), if it was successful.
	 * If attemptSave() has not yet been called or failed, null is returned.
	 *
	 * @since 0.3
	 *
	 * @return EntityRevision|null
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

		if ( !is_int( $this->getBaseRevisionId() ) || $this->getBaseRevisionId() == $this->getLatestRevisionId() ) {
			wfProfileOut( __METHOD__ );
			return false;
		}

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * Attempts to fix an edit conflict by patching the intended change into the latest revision after
	 * checking for conflicts. This modifies $this->newEntity but does not write anything to the
	 * database. Saving of the new content may still fail.
	 *
	 * @return bool True if the conflict could be resolved, false otherwise
	 */
	public function fixEditConflict() {
		$baseRev = $this->getBaseRevision();
		$latestRev = $this->getLatestRevision();
		$newEntity = $this->getNewEntity();

		if ( !$latestRev ) {
			wfLogWarning( 'Failed to load latest revision of entity ' . $this->getEntityId() . '! '
				. 'This may indicate entries missing from thw wb_entities_per_page table.' );
			return false;
		}

		// calculate patch against base revision
		// NOTE: will fail if $baseRev or $base are null, which they may be if
		// this gets called at an inappropriate time. The data flow in this class
		// should be improved.
		$patch = $baseRev->getEntity()->getDiff( $newEntity ); // diff from base to new

		if ( $patch->isEmpty() ) {
			// we didn't technically fix anything, but if there is nothing to change,
			// so just keep the current content as it is.
			$this->newEntity = $latestRev->getEntity()->copy();
			return true;
		}

		// apply the patch( base -> new ) to the latest revision.
		$patchedLatest = $latestRev->getEntity()->copy();
		$patchedLatest->patch( $patch );

		// detect conflicts against latest revision
		$cleanPatch = $latestRev->getEntity()->getDiff( $patchedLatest );

		$conflicts = $patch->count() - $cleanPatch->count();

		if ( $conflicts > 0 ) {
			// patch doesn't apply cleanly
			if ( $this->userWasLastToEdit( $this->getUser(), $this->getEntityId(), $this->getBaseRevisionId() ) ) {
				// it's a self-conflict
				if ( $cleanPatch->count() === 0 ) {
					// patch collapsed, possibly because of diff operation change from base to latest
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

		// remember the patched entity as the actual new entity to save
		$this->newEntity = $patchedLatest;

		return true;
	}

	/**
	 * Check if no edits were made by other users since the given revision.
	 * This makes the assumption that revision ids are monotonically increasing.
	 *
	 * @param User $user
	 * @param EntityId $entityId
	 * @param int|bool $lastRevId
	 *
	 * @return bool
	 */
	protected function userWasLastToEdit( User $user = null, EntityId $entityId = null, $lastRevId = false ) {
		if ( $user === null ||  $entityId === null || $lastRevId === false ) {
			return false;
		}

		return $this->entityStore->userWasLastToEdit( $user, $entityId, $lastRevId );
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
			$permissionStatus = $this->permissionChecker->getPermissionStatusForEntity(
				$this->user,
				$action,
				$this->newEntity );

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
	 * @param int         $flags      The edit flags (see WikiPage::doEditContent)
	 * @param String|bool $token      Edit token to check, or false to disable the token check.
	 *                                Null will fail the token text, as will the empty string.
	 * @param bool|null $watch        Whether the user wants to watch the entity.
	 *                                Set to null to apply default according to getWatchDefault().
	 *
	 * @throws ReadOnlyError
	 * @return Status Indicates success and provides detailed warnings or error messages. See
	 *         getStatus() for more details.
	 * @see    WikiPage::doEditContent
	 */
	public function attemptSave( $summary, $flags, $token, $watch = null ) {
		wfProfileIn( __METHOD__ );

		if ( wfReadOnly() ) {
			throw new ReadOnlyError();
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

		//NOTE: Make sure the latest revision is loaded and cached.
		//      Would happen on demand anyway, but we want a well-defined point at which "latest" is frozen
		//      to a specific revision, just before the first check for edit conflicts.
		$this->getLatestRevision();
		$this->getLatestRevisionId();

		$this->applyPreSaveChecks(); // modifies $this->status

		if ( !$this->status->isOK() ) {
			$this->errorType |= self::PRECONDITION_FAILED;
		}

		if ( !$this->status->isOK() ) {
			$this->status->setResult( false, array( 'errorFlags' => $this->errorType ) );

			wfProfileOut( __METHOD__ );
			return $this->status;
		}

		$this->runEditFilterHooks( $summary );

		if ( !$this->status->isOK() ) {
			$this->status->setResult( false, array( 'errorFlags' => $this->errorType ) );

			wfProfileOut( __METHOD__ );
			return $this->status;
		}

		try {
			$entityRevision = $this->entityStore->saveEntity(
				$this->newEntity,
				$summary,
				$this->getUser(),
				$flags | EDIT_AUTOSUMMARY,
				$this->doesCheckForEditConflicts() ? $this->getLatestRevisionId() : false
			);

			$editStatus = Status::newGood( array( 'revision' => $entityRevision ) );
		} catch ( StorageException $ex ) {
			$editStatus = $ex->getStatus();

			if ( $editStatus === null ) {
				// XXX: perhaps internalerror_info isn't the best, but we need some generic error message.
				$editStatus = Status::newFatal( 'internalerror_info', $ex->getMessage() );
			}

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

	/**
	 * Call EditFilterMergedContent hook, if registered.
	 *
	 * @param string $summary
	 *
	 * @todo: move the implementation elsewhere, it depends on WikiPage.
	 */
	protected function runEditFilterHooks( $summary ) {
		if ( !Hooks::isRegistered( 'EditFilterMergedContent' ) ) {
			return;
		}

		if ( !$this->isNew() ) {
			$context = clone $this->context;

			$title = $this->getTitle();
			$context->setTitle( $title );
			$context->setWikiPage( new WikiPage( $title ) );
		} else {
			$context = $this->context;
		}

		// Run edit filter hooks
		$filterStatus = Status::newGood();

		$entityContentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();
		$entityContent = $entityContentFactory->newFromEntity( $this->newEntity );

		if ( !wfRunHooks( 'EditFilterMergedContent',
			array( $context, $entityContent, &$filterStatus, $summary, $this->getUser(), false ) ) ) {

			# Error messages etc. were handled inside the hook.
			$filterStatus->setResult( false, $filterStatus->getValue() );
		}

		if ( !$filterStatus->isOK() ) {
			$this->errorType |= self::FILTERED;
		}

		$this->status->merge( $filterStatus );
	}

	protected function applyPreSaveChecks() {
		if ( $this->hasEditConflict() ) {
			if ( !$this->fixEditConflict() ) {
				$this->status->fatal( 'edit-conflict' );
				$this->errorType |= self::EDIT_CONFLICT_ERROR;

				return $this->status;
			}
		}

		$this->getBaseRevision();

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

		$text = $this->status->getHTML();

		$out->addHTML( Html::rawElement( 'div', array( 'class' => 'error' ), $text ) );

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
	protected function getWatchDefault() {
		$user = $this->getUser();

		if ( $user->getOption( 'watchdefault' ) ) {
			// Watch all edits
			return true;
		} elseif ( $user->getOption( 'watchcreations' ) && $this->isNew() ) {
			// Watch creations
			return true;
		}

		// keep current state
		return !$this->isNew() && $this->entityStore->isWatching( $user, $this->getEntityId() );
	}

	/**
	 * Watches or unwatches the entity.
	 *
	 * @todo: move to separate service
	 *
	 * @param bool $watch whether to watch or unwatch the page.
	 *
	 * @throws MWException
	 * @return void : keep in sync with logic in EditPage
	 */
	public function updateWatchlist( $watch ) {
		$user = $this->getUser();
		$title = $this->getTitle();

		if ( !$title ) {
			throw new MWException( "Title not yet known!" );
		}

		$this->entityStore->updateWatchlist( $user, $this->getEntityId(), $watch );
	}
}

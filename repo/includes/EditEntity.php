<?php

namespace Wikibase;

use InvalidArgumentException;
use MWException;
use ReadOnlyError;
use RuntimeException;
use Status;
use Title;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Services\Diff\EntityPatcher;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Hooks\EditFilterHookRunner;
use Wikibase\Repo\Store\EntityPermissionChecker;

/**
 * Handler for editing activity, providing a unified interface for saving modified entities while performing
 * permission checks and handling edit conflicts.
 *
 * @license GPL-2.0+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class EditEntity {

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var EntityTitleStoreLookup
	 */
	private $titleLookup;

	/**
	 * @var EntityStore
	 */
	private $entityStore;

	/**
	 * @var EntityPermissionChecker
	 */
	private $permissionChecker;

	/**
	 * @var EntityDiffer
	 */
	private $entityDiffer;

	/**
	 * @var EntityPatcher
	 */
	private $entityPatcher;

	/**
	 * The ID of the entity to edit. May be null if a new entity is being created.
	 *
	 * @var EntityId|null
	 */
	private $entityId = null;

	/**
	 * @var EntityRevision|null
	 */
	private $baseRev = null;

	/**
	 * @var int|bool
	 */
	private $baseRevId;

	/**
	 * @var EntityRevision|null
	 */
	private $latestRev = null;

	/**
	 * @var int
	 */
	private $latestRevId = 0;

	/**
	 * @var Status|null
	 */
	private $status = null;

	/**
	 * @var User|null
	 */
	private $user = null;

	/**
	 * @var Title|null
	 */
	private $title = null;

	/**
	 * @var EditFilterHookRunner
	 */
	private $editFilterHookRunner;

	/**
	 * @var int Bit field for error types, using the EditEntity::XXX_ERROR constants.
	 */
	private $errorType = 0;

	/**
	 * @var bool Can use a master connection or not
	 */
	private $allowMasterConnection;

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
	 * @param EntityTitleStoreLookup $titleLookup
	 * @param EntityRevisionLookup $entityLookup
	 * @param EntityStore $entityStore
	 * @param EntityPermissionChecker $permissionChecker
	 * @param EntityDiffer $entityDiffer
	 * @param EntityPatcher $entityPatcher
	 * @param EntityId|null $entityId the ID of the entity being edited.
	 *        May be null when creating a new entity.
	 * @param User $user the user performing the edit
	 * @param EditFilterHookRunner $editFilterHookRunner
	 * @param int $baseRevId the base revision ID for conflict checking.
	 *        Use 0 to indicate that the current revision should be used as the base revision,
	 *        effectively disabling conflict detections. true and false will be accepted for
	 *        backwards compatibility, but both will be treated like 0. Note that the behavior
	 *        of this class changed so that "late" conflicts that arise between edit conflict
	 *        detection and database update are always detected, and result in the update to fail.
	 */
	public function __construct(
		EntityTitleStoreLookup $titleLookup,
		EntityRevisionLookup $entityLookup,
		EntityStore $entityStore,
		EntityPermissionChecker $permissionChecker,
		EntityDiffer $entityDiffer,
		EntityPatcher $entityPatcher,
		EntityId $entityId = null,
		User $user,
		EditFilterHookRunner $editFilterHookRunner,
		$baseRevId = 0,
		$allowMasterConnection = true
	) {
		$this->entityId = $entityId;

		if ( is_string( $baseRevId ) ) {
			$baseRevId = (int)$baseRevId;
		}

		if ( is_bool( $baseRevId ) ) {
			$baseRevId = 0;
		}

		$this->user = $user;
		$this->baseRevId = $baseRevId;

		$this->errorType = 0;
		$this->status = Status::newGood();

		$this->titleLookup = $titleLookup;
		$this->entityRevisionLookup = $entityLookup;
		$this->entityStore = $entityStore;
		$this->permissionChecker = $permissionChecker;
		$this->entityDiffer = $entityDiffer;
		$this->entityPatcher = $entityPatcher;

		$this->editFilterHookRunner = $editFilterHookRunner;
		$this->allowMasterConnection = $allowMasterConnection;
	}

	/**
	 * Returns the ID of the entity being edited.
	 * May be null if a new entity is to be created.
	 *
	 * @return null|EntityId
	 */
	public function getEntityId() {
		return $this->entityId;
	}

	/**
	 * Returns the Title of the page holding the entity that is being edited.
	 *
	 * @return Title|null
	 */
	private function getTitle() {
		if ( $this->title === null ) {
			$id = $this->getEntityId();

			if ( $id !== null ) {
				$this->title = $this->titleLookup->getTitleForId( $id );
			}
		}

		return $this->title;
	}

	/**
	 * Returns the latest revision of the entity.
	 *
	 * @return EntityRevision|null
	 */
	public function getLatestRevision() {
		if ( $this->latestRev === null ) {
			$id = $this->getEntityId();

			if ( $id !== null ) {
				// NOTE: It's important to remember this, if someone calls clear() on
				// $this->getPage(), this should NOT change!
				$this->latestRev = $this->entityRevisionLookup->getEntityRevision(
					$id,
					0,
					$this->getReplicaMode()
				);
			}
		}

		return $this->latestRev;
	}

	/**
	 * @return int 0 if the entity doesn't exist
	 */
	private function getLatestRevisionId() {
		// Don't do negative caching: We call this to see whether the entity yet exists
		// before creating.
		if ( $this->latestRevId === 0 ) {
			$id = $this->getEntityId();

			if ( $this->latestRev !== null ) {
				$this->latestRevId = $this->latestRev->getRevisionId();
			} elseif ( $id !== null ) {
				$this->latestRevId = (int)$this->entityRevisionLookup->getLatestRevisionId(
					$id,
					$this->getReplicaMode()
				);
			}
		}

		return $this->latestRevId;
	}

	/**
	 * Is the entity new?
	 * An entity is new in case it either doesn't have an id or the Title belonging
	 * to it doesn't (yet) exist.
	 *
	 * @return bool
	 */
	private function isNew() {
		return $this->getEntityId() === null || $this->getLatestRevisionId() === 0;
	}

	/**
	 * Return the ID of the base revision for the edit. If no base revision ID was supplied to
	 * the constructor, this returns the ID of the latest revision. If the entity does not exist
	 * yet, this returns 0.
	 *
	 * @return int
	 */
	private function getBaseRevisionId() {
		if ( $this->baseRevId === 0 ) {
			$this->baseRevId = $this->getLatestRevisionId();
		}

		return $this->baseRevId;
	}

	/**
	 * Return the the base revision for the edit. If no base revision ID was supplied to
	 * the constructor, this returns the latest revision. If the entity does not exist
	 * yet, this returns null.
	 *
	 * @return EntityRevision|null
	 * @throws MWException
	 */
	public function getBaseRevision() {
		if ( $this->baseRev === null ) {
			$baseRevId = $this->getBaseRevisionId();

			if ( $baseRevId === $this->getLatestRevisionId() ) {
				$this->baseRev = $this->getLatestRevision();
			} else {
				$id = $this->getEntityId();

				$this->baseRev = $this->entityRevisionLookup->getEntityRevision(
					$id,
					$baseRevId,
					$this->getReplicaMode()
				);

				if ( $this->baseRev === null ) {
					throw new MWException( 'Base revision ID not found: rev ' . $baseRevId
						. ' of ' . $id->getSerialization() );
				}
			}
		}

		return $this->baseRev;
	}

	/**
	 * @return string
	 */
	private function getReplicaMode() {
		if ( $this->allowMasterConnection === true ) {
			return EntityRevisionLookup::LATEST_FROM_REPLICA_WITH_FALLBACK;
		} else {
			return EntityRevisionLookup::LATEST_FROM_REPLICA;
		}
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
	 * @return Status
	 */
	public function getStatus() {
		if ( $this->status === null ) {
			throw new RuntimeException( 'The status is undefined until attemptSave() has been called' );
		}

		return $this->status;
	}

	/**
	 * Determines whether the last call to attemptSave was successful.
	 *
	 * @return bool false if attemptSave() failed, true otherwise
	 */
	public function isSuccess() {
		return $this->errorType === 0 && $this->status->isOK();
	}

	/**
	 * Checks whether this EditEntity encountered any of the given error types while executing attemptSave().
	 *
	 * @param int $errorType bit field using the EditEntity::XXX_ERROR constants.
	 *            Defaults to EditEntity::ANY_ERROR.
	 *
	 * @return bool true if this EditEntity encountered any of the error types in $errorType, false otherwise.
	 */
	public function hasError( $errorType = self::ANY_ERROR ) {
		return ( $this->errorType & $errorType ) !== 0;
	}

	/**
	 * Determines whether an edit conflict exists, that is, whether another user has edited the
	 * same item after the base revision was created. In other words, this method checks whether
	 * the base revision (as provided to the constructor) is still current. If no base revision
	 * was provided to the constructor, this will always return false.
	 *
	 * If the base revision is different from the current revision, this will return true even if
	 * the edit conflict is resolvable. Indeed, it is used to determine whether conflict resolution
	 * should be attempted.
	 *
	 * @return bool
	 */
	public function hasEditConflict() {
		return !$this->isNew()
			&& $this->getBaseRevisionId() !== $this->getLatestRevisionId();
	}

	/**
	 * Attempts to fix an edit conflict by patching the intended change into the latest revision after
	 * checking for conflicts.
	 *
	 * @param EntityDocument $newEntity
	 *
	 * @throws MWException
	 * @return null|EntityDocument The patched Entity, or null if patching failed.
	 */
	private function fixEditConflict( EntityDocument $newEntity ) {
		$baseRev = $this->getBaseRevision();
		$latestRev = $this->getLatestRevision();

		if ( !$latestRev ) {
			wfLogWarning( 'Failed to load latest revision of entity ' . $newEntity->getId() . '! '
				. 'This may indicate entries missing from thw wb_entities_per_page table.' );
			return null;
		}

		// calculate patch against base revision
		// NOTE: will fail if $baseRev or $base are null, which they may be if
		// this gets called at an inappropriate time. The data flow in this class
		// should be improved.
		$patch = $this->entityDiffer->diffEntities( $baseRev->getEntity(), $newEntity );

		if ( $patch->isEmpty() ) {
			// we didn't technically fix anything, but if there is nothing to change,
			// so just keep the current content as it is.
			return $latestRev->getEntity()->copy();
		}

		// apply the patch( base -> new ) to the latest revision.
		$patchedLatest = $latestRev->getEntity()->copy();
		$this->entityPatcher->patchEntity( $patchedLatest, $patch );

		// detect conflicts against latest revision
		$cleanPatch = $this->entityDiffer->diffEntities( $latestRev->getEntity(), $patchedLatest );

		$conflicts = $patch->count() - $cleanPatch->count();

		if ( $conflicts > 0 ) {
			// patch doesn't apply cleanly
			if ( $this->userWasLastToEdit( $this->user, $newEntity->getId(), $this->getBaseRevisionId() ) ) {
				// it's a self-conflict
				if ( $cleanPatch->count() === 0 ) {
					// patch collapsed, possibly because of diff operation change from base to latest
					return null;
				} else {
					// we still have a working patch, try to apply
					$this->status->warning( 'wikibase-self-conflict-patched' );
				}
			} else {
				// there are unresolvable conflicts.
				return null;
			}
		} else {
			// can apply cleanly
			$this->status->warning( 'wikibase-conflict-patched' );
		}

		// return the patched entity
		return $patchedLatest;
	}

	/**
	 * Check if no edits were made by other users since the given revision.
	 * This makes the assumption that revision ids are monotonically increasing.
	 *
	 * @param User|null $user
	 * @param EntityId|null $entityId
	 * @param int|bool $lastRevId
	 *
	 * @return bool
	 */
	private function userWasLastToEdit( User $user = null, EntityId $entityId = null, $lastRevId = false ) {
		if ( $user === null || $entityId === null || $lastRevId === false ) {
			return false;
		}

		return $this->entityStore->userWasLastToEdit( $user, $entityId, $lastRevId );
	}

	/**
	 * Checks the necessary permissions to perform this edit.
	 * Per default, the 'edit' permission is checked.
	 * Use addRequiredPermission() to check more permissions.
	 *
	 * @param EntityDocument $newEntity
	 */
	private function checkEditPermissions( EntityDocument $newEntity ) {
		$permissionStatus = $this->permissionChecker->getPermissionStatusForEntity(
			$this->user,
			EntityPermissionChecker::ACTION_EDIT,
			$newEntity
		);

		$this->status->merge( $permissionStatus );

		if ( !$this->status->isOK() ) {
			$this->errorType |= self::PERMISSION_ERROR;
			$this->status->fatal( 'no-permission' );
		}
	}

	/**
	 * Checks if rate limits have been exceeded.
	 */
	private function checkRateLimits() {
		if ( $this->user->pingLimiter( 'edit' )
			|| ( $this->isNew() && $this->user->pingLimiter( 'create' ) )
		) {
			$this->errorType |= self::RATE_LIMIT;
			$this->status->fatal( 'actionthrottledtext' );
		}
	}

	/**
	 * Make sure the given WebRequest contains a valid edit token.
	 *
	 * @param string $token The token to check.
	 *
	 * @return bool true if the token is valid
	 */
	public function isTokenOK( $token ) {
		$tokenOk = $this->user->matchEditToken( $token );
		$tokenOkExceptSuffix = $this->user->matchEditTokenNoSuffix( $token );

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
	 * Resolve user specific default default for watch state, if $watch is null.
	 *
	 * @param boolean|null $watch
	 *
	 * @return bool
	 */
	private function getDesiredWatchState( $watch ) {
		if ( $watch === null ) {
			$watch = $this->getWatchDefault();
		}

		return $watch;
	}

	/**
	 * @param EntityId|null $id
	 *
	 * @throws InvalidArgumentException
	 */
	private function checkEntityId( EntityId $id = null ) {
		if ( $this->entityId ) {
			if ( !$this->entityId->equals( $id ) ) {
				throw new InvalidArgumentException(
					'Expected the EntityDocument to have ID ' . $this->entityId->getSerialization()
					. ', found ' . ( $id ? $id->getSerialization() : 'null' )
				);
			}
		}
	}

	/**
	 * @throws ReadOnlyError
	 */
	private function checkReadOnly() {
		if ( wfReadOnly() ) {
			throw new ReadOnlyError();
		}
	}

	/**
	 * Attempts to save the given Entity object.
	 *
	 * This method performs entity level permission checks, checks the edit toke, enforces rate
	 * limits, resolves edit conflicts, and updates user watchlists if appropriate.
	 *
	 * Success or failure are reported via the STatus object returned by this method.
	 *
	 * @param EntityDocument $newEntity
	 * @param string $summary The edit summary.
	 * @param int $flags The EDIT_XXX flags as used by WikiPage::doEditContent().
	 *        Additionally, the EntityContent::EDIT_XXX constants can be used.
	 * @param string|bool $token Edit token to check, or false to disable the token check.
	 *                                Null will fail the token text, as will the empty string.
	 * @param bool|null $watch Whether the user wants to watch the entity.
	 *                                Set to null to apply default according to getWatchDefault().
	 *
	 * @return Status
	 *
	 * @throws MWException
	 * @throws ReadOnlyError
	 *
	 * @see    WikiPage::doEditContent
	 * @see    EntityStore::saveEntity
	 */
	public function attemptSave( EntityDocument $newEntity, $summary, $flags, $token, $watch = null ) {
		$this->checkReadOnly();
		$this->checkEntityId( $newEntity->getId() );

		$watch = $this->getDesiredWatchState( $watch );

		$this->status = Status::newGood();
		$this->errorType = 0;

		if ( $token !== false && !$this->isTokenOK( $token ) ) {
			//@todo: This is redundant to the error code set in isTokenOK().
			//       We should figure out which error codes the callers expect,
			//       and only set the correct error code, in one place, probably here.
			$this->errorType |= self::TOKEN_ERROR;
			$this->status->fatal( 'sessionfailure' );
			$this->status->setResult( false, [ 'errorFlags' => $this->errorType ] );
			return $this->status;
		}

		$this->checkEditPermissions( $newEntity );

		$this->checkRateLimits(); // modifies $this->status

		if ( !$this->status->isOK() ) {
			$this->status->setResult( false, [ 'errorFlags' => $this->errorType ] );
			return $this->status;
		}

		//NOTE: Make sure the latest revision is loaded and cached.
		//      Would happen on demand anyway, but we want a well-defined point at which "latest" is
		//      frozen to a specific revision, just before the first check for edit conflicts.
		//      We can use the ID of the latest revision to protect against race conditions:
		//      if getLatestRevision() was called earlier by application logic, saving will fail
		//      if any new revisions were created between then and now.
		//      Note that this protection against "late" conflicts is unrelated to the detection
		//      of edit conflicts during user interaction, which use the base revision supplied
		//      to the constructor.
		$this->getLatestRevision();
		$raceProtectionRevId = $this->getLatestRevisionId();

		if ( $raceProtectionRevId === 0 ) {
			$raceProtectionRevId = false;
		}

		if ( $this->hasEditConflict() ) {
			$newEntity = $this->fixEditConflict( $newEntity );

			if ( !$newEntity ) {
				$this->errorType |= self::EDIT_CONFLICT_ERROR;
				$this->status->setResult( false, [ 'errorFlags' => $this->errorType ] );
				$this->status->error( 'edit-conflict' );

				return $this->status;
			}
		}

		if ( !$this->status->isOK() ) {
			$this->errorType |= self::PRECONDITION_FAILED;
		}

		if ( !$this->status->isOK() ) {
			$this->status->setResult( false, [ 'errorFlags' => $this->errorType ] );
			return $this->status;
		}

		$hookStatus = $this->editFilterHookRunner->run( $newEntity, $this->user, $summary );
		if ( !$hookStatus->isOK() ) {
			$this->errorType |= self::FILTERED;
		}
		$this->status->merge( $hookStatus );

		if ( !$this->status->isOK() ) {
			$this->status->setResult( false, [ 'errorFlags' => $this->errorType ] );
			return $this->status;
		}

		try {
			$entityRevision = $this->entityStore->saveEntity(
				$newEntity,
				$summary,
				$this->user,
				$flags | EDIT_AUTOSUMMARY,
				$raceProtectionRevId
			);

			$this->entityId = $newEntity->getId();
			$editStatus = Status::newGood( [ 'revision' => $entityRevision ] );
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

		return $this->status;
	}

	/**
	}

	/**
	 * Returns whether the present edit would, per default,
	 * lead to the user watching the page.
	 *
	 * This uses the user's watchdefault and watchcreations settings
	 * and considers whether the entity is already watched by the user.
	 *
	 * @note Keep in sync with logic in EditPage!
	 *
	 * @return bool
	 */
	private function getWatchDefault() {
		// User wants to watch all edits or all creations.
		if ( $this->user->getOption( 'watchdefault' )
			|| ( $this->user->getOption( 'watchcreations' ) && $this->isNew() )
		) {
			return true;
		}

		// keep current state
		return !$this->isNew() && $this->entityStore->isWatching( $this->user, $this->getEntityId() );
	}

	/**
	 * Watches or unwatches the entity.
	 *
	 * @note Keep in sync with logic in EditPage!
	 * @todo: move to separate service
	 *
	 * @param bool $watch whether to watch or unwatch the page.
	 *
	 * @throws MWException
	 */
	private function updateWatchlist( $watch ) {
		if ( $this->getTitle() === null ) {
			throw new MWException( 'Title not yet known!' );
		}

		$this->entityStore->updateWatchlist( $this->user, $this->getEntityId(), $watch );
	}

}

<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\EditEntity;

use InvalidArgumentException;
use MediaWiki\Context\DerivativeContext;
use MediaWiki\Context\IContextSource;
use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\Status\Status;
use MediaWiki\Title\Title;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\TempUser\TempUserCreator;
use MediaWiki\User\User;
use ReadOnlyError;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Services\Diff\EntityPatcher;
use Wikibase\Lib\Store\EntityContentTooBigException;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\WikibaseRepo;

/**
 * Handler for editing activity, providing a unified interface for saving modified entities while performing
 * permission checks and handling edit conflicts.
 *
 * @license GPL-2.0-or-later
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class MediaWikiEditEntity implements EditEntity {

	private EntityRevisionLookup $entityRevisionLookup;
	private EntityTitleStoreLookup $titleLookup;
	private EntityStore $entityStore;
	private EntityPermissionChecker $permissionChecker;
	private EntityDiffer $entityDiffer;
	private EntityPatcher $entityPatcher;
	/** The ID of the entity to edit. May be null if a new entity is being created. */
	private ?EntityId $entityId;
	private ?EntityRevision $baseRev = null;
	/** @var int|bool */
	private $baseRevId;
	private ?EntityRevision $latestRev = null;
	private int $latestRevId = 0;
	private EditEntityStatus $status;
	private IContextSource $context;
	private User $user;
	private ?Title $title = null;
	private EditFilterHookRunner $editFilterHookRunner;
	private UserOptionsLookup $userOptionsLookup;
	private TempUserCreator $tempUserCreator;
	/** @var int Bit field for error types, using the EditEntity::XXX_ERROR constants. */
	private int $errorType = 0;
	private int $maxSerializedEntitySize;
	/** @var string[] */
	private array $localEntityTypes;
	private bool $allowMasterConnection;

	/**
	 * @param EntityTitleStoreLookup $titleLookup
	 * @param EntityRevisionLookup $entityLookup
	 * @param EntityStore $entityStore
	 * @param EntityPermissionChecker $permissionChecker
	 * @param EntityDiffer $entityDiffer
	 * @param EntityPatcher $entityPatcher
	 * @param EntityId|null $entityId the ID of the entity being edited.
	 *        May be null when creating a new entity.
	 * @param IContextSource $context the request context for the edit
	 * @param EditFilterHookRunner $editFilterHookRunner
	 * @param UserOptionsLookup $userOptionsLookup
	 * @param TempUserCreator $tempUserCreator
	 * @param int $maxSerializedEntitySize the maximal allowed entity size in Kilobytes
	 * @param string[] $localEntityTypes
	 * @param int $baseRevId the base revision ID for conflict checking.
	 *        Use 0 to indicate that the current revision should be used as the base revision,
	 *        effectively disabling conflict detections. Note that the behavior
	 *        of this class changed so that "late" conflicts that arise between edit conflict
	 *        detection and database update are always detected, and result in the update to fail.
	 * @param bool $allowMasterConnection
	 */
	public function __construct(
		EntityTitleStoreLookup $titleLookup,
		EntityRevisionLookup $entityLookup,
		EntityStore $entityStore,
		EntityPermissionChecker $permissionChecker,
		EntityDiffer $entityDiffer,
		EntityPatcher $entityPatcher,
		?EntityId $entityId,
		IContextSource $context,
		EditFilterHookRunner $editFilterHookRunner,
		UserOptionsLookup $userOptionsLookup,
		TempUserCreator $tempUserCreator,
		int $maxSerializedEntitySize,
		array $localEntityTypes,
		int $baseRevId = 0,
		bool $allowMasterConnection = true
	) {
		$this->entityId = $entityId;

		$this->context = $context;
		$this->user = $context->getUser();
		$this->baseRevId = $baseRevId;

		$this->errorType = 0;
		$this->status = EditEntityStatus::newGood();

		$this->titleLookup = $titleLookup;
		$this->entityRevisionLookup = $entityLookup;
		$this->entityStore = $entityStore;
		$this->permissionChecker = $permissionChecker;
		$this->entityDiffer = $entityDiffer;
		$this->entityPatcher = $entityPatcher;

		$this->editFilterHookRunner = $editFilterHookRunner;
		$this->userOptionsLookup = $userOptionsLookup;
		$this->tempUserCreator = $tempUserCreator;
		$this->allowMasterConnection = $allowMasterConnection;
		$this->maxSerializedEntitySize = $maxSerializedEntitySize;
		$this->localEntityTypes = $localEntityTypes;
	}

	/**
	 * Returns the ID of the entity being edited.
	 * May be null if a new entity is to be created.
	 */
	public function getEntityId(): ?EntityId {
		return $this->entityId;
	}

	/**
	 * Returns the Title of the page holding the entity that is being edited.
	 */
	private function getTitle(): ?Title {
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
	 */
	public function getLatestRevision(): ?EntityRevision {
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
	private function getLatestRevisionId(): int {
		// Don't do negative caching: We call this to see whether the entity yet exists
		// before creating.
		if ( $this->latestRevId === 0 ) {
			$id = $this->getEntityId();

			if ( $this->latestRev !== null ) {
				$this->latestRevId = $this->latestRev->getRevisionId();
			} elseif ( $id !== null ) {
				$result = $this->entityRevisionLookup->getLatestRevisionId(
					$id,
					$this->getReplicaMode()
				);
				$returnZero = static function () {
					return 0;
				};
				$this->latestRevId = $result->onNonexistentEntity( $returnZero )
					->onRedirect( $returnZero )
					->onConcreteRevision( function ( $revId ) {
						return $revId;
					} )
					->map();
			}
		}

		return $this->latestRevId;
	}

	/**
	 * Is the entity new?
	 */
	private function isNew(): bool {
		return $this->getEntityId() === null || $this->getLatestRevisionId() === 0;
	}

	/**
	 * Does this entity belong to a new page?
	 * (An entity may {@link isNew be new}, and yet not belong to a new page,
	 * e.g. if it is stored in a non-main slot.)
	 */
	private function isNewPage(): bool {
		$title = $this->getTitle();
		return !$title || !$title->exists();
	}

	/**
	 * Return the ID of the base revision for the edit. If no base revision ID was supplied to
	 * the constructor, this returns the ID of the latest revision. If the entity does not exist
	 * yet, this returns 0.
	 */
	private function getBaseRevisionId(): int {
		if ( $this->baseRevId === 0 ) {
			$this->baseRevId = $this->getLatestRevisionId();
		}

		return $this->baseRevId;
	}

	/**
	 * Return the base revision for the edit. If no base revision ID was supplied to
	 * the constructor, this returns the latest revision. If the entity does not exist
	 * yet, this returns null.
	 *
	 * @return EntityRevision|null
	 * @throws RevisionedUnresolvedRedirectException
	 * @throws StorageException
	 */
	public function getBaseRevision(): ?EntityRevision {
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
					throw new RuntimeException( 'Base revision ID not found: rev ' . $baseRevId
						. ' of ' . $id->getSerialization() );
				}
			}
		}

		return $this->baseRev;
	}

	private function getReplicaMode(): string {
		if ( $this->allowMasterConnection ) {
			return LookupConstants::LATEST_FROM_REPLICA_WITH_FALLBACK;
		} else {
			return LookupConstants::LATEST_FROM_REPLICA;
		}
	}

	public function getStatus(): EditEntityStatus {
		return $this->status;
	}

	/**
	 * Determines whether the last call to attemptSave was successful.
	 *
	 * @return bool false if attemptSave() failed, true otherwise
	 */
	public function isSuccess(): bool {
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
	public function hasError( $errorType = EditEntity::ANY_ERROR ): bool {
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
	 */
	public function hasEditConflict(): bool {
		return !$this->isNew()
			&& $this->getBaseRevisionId() !== $this->getLatestRevisionId();
	}

	/**
	 * Attempts to fix an edit conflict by patching the intended change into the latest revision after
	 * checking for conflicts.
	 *
	 * @param EntityDocument $newEntity
	 *
	 * @return null|EntityDocument The patched Entity, or null if patching failed.
	 */
	private function fixEditConflict( EntityDocument $newEntity ): ?EntityDocument {
		$baseRev = $this->getBaseRevision();
		$latestRev = $this->getLatestRevision();

		if ( !$latestRev ) {
			wfLogWarning( 'Failed to load latest revision of entity ' . $newEntity->getId() . '!' );
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

		if ( $conflicts !== 0 ) {
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
	private function userWasLastToEdit( User $user = null, EntityId $entityId = null, $lastRevId = false ): bool {
		if ( $user === null || $entityId === null || $lastRevId === false ) {
			return false;
		}

		return $this->entityStore->userWasLastToEdit( $user, $entityId, $lastRevId );
	}

	/**
	 * Checks the necessary permissions to perform this edit.
	 * The 'edit' permission is always checked (currently not configurable).
	 */
	private function checkEditPermissions( EntityDocument $newEntity ): void {
		$permissionStatus = $this->permissionChecker->getPermissionStatusForEntity(
			$this->user,
			EntityPermissionChecker::ACTION_EDIT,
			$newEntity
		);

		$this->status->merge( $permissionStatus );

		if ( !$this->status->isOK() ) {
			$this->errorType |= EditEntity::PERMISSION_ERROR;
			$this->status->fatal( 'permissionserrors' );
		}
	}

	/**
	 * Checks if rate limits have been exceeded.
	 */
	private function checkRateLimits(): void {
		if ( $this->user->pingLimiter( 'edit' )
			|| ( $this->isNew() && $this->user->pingLimiter( 'create' ) )
		) {
			$this->errorType |= EditEntity::RATE_LIMIT_ERROR;
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
	public function isTokenOK( $token ): bool {
		$tokenOk = $this->user->matchEditToken( $token );

		if ( !$tokenOk ) {
			$this->status->fatal( 'session_fail_preview' );
			$this->errorType |= EditEntity::TOKEN_ERROR;
			return false;
		}

		return true;
	}

	/**
	 * Resolve user specific default default for watch state, if $watch is null.
	 */
	private function getDesiredWatchState( ?bool $watch ): bool {
		if ( $watch === null ) {
			$watch = $this->getWatchDefault();
		}

		return $watch;
	}

	/**
	 * @throws InvalidArgumentException
	 */
	private function checkEntityId( EntityId $id = null ): void {
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
	private function checkReadOnly( EntityDocument $entity ): void {
		$services = MediaWikiServices::getInstance();
		if ( $services->getReadOnlyMode()->isReadOnly() ) {
			throw new ReadOnlyError();
		}
		if ( $this->entityTypeIsReadOnly( $entity ) ) {
			$services->getReadOnlyMode()->setReason(
				'Editing of entity type: ' . $entity->getType() . ' is currently disabled. It will be enabled soon.'
			);
			throw new ReadOnlyError();
		}
	}

	private function entityTypeIsReadOnly( EntityDocument $entity ): bool {
		$readOnlyTypes = WikibaseRepo::getSettings()->getSetting( 'readOnlyEntityTypes' );

		return in_array( $entity->getType(), $readOnlyTypes );
	}

	/** Modifies $this->status and $this->errorType. Does not throw. */
	private function checkLocal( EntityDocument $entity ): void {
		if ( !$this->entityTypeIsLocal( $entity ) ) {
			$this->errorType |= EditEntity::PRECONDITION_FAILED_ERROR;
			$this->status->fatal(
				'wikibase-error-entity-not-local',
				Message::plaintextParam( $entity->getType() )
			);
		}
	}

	private function entityTypeIsLocal( EntityDocument $entity ): bool {
		return in_array( $entity->getType(), $this->localEntityTypes );
	}

	public function attemptSave( EntityDocument $newEntity, string $summary, $flags, $token, $watch = null, array $tags = [] ) {
		$this->checkReadOnly( $newEntity ); // throws, exception formatted by MediaWiki (cf. MWExceptionRenderer::getExceptionTitle)
		$this->checkEntityId( $newEntity->getId() ); // throws internal error (unexpected condition)

		$watch = $this->getDesiredWatchState( $watch );

		$this->status = EditEntityStatus::newGood();
		$this->errorType = 0;

		$this->checkLocal( $newEntity ); // modifies $this->status

		if ( $token !== false && !$this->isTokenOK( $token ) ) {
			//@todo: This is redundant to the error code set in isTokenOK().
			//       We should figure out which error codes the callers expect,
			//       and only set the correct error code, in one place, probably here.
			$this->errorType |= EditEntity::TOKEN_ERROR;
			$this->status->fatal( 'sessionfailure' );
		}

		if ( !$this->status->isOK() ) {
			$this->status->setErrorFlags( $this->errorType );
			return $this->status;
		}

		$this->checkEditPermissions( $newEntity );

		$this->checkRateLimits(); // modifies $this->status

		if ( !$this->status->isOK() ) {
			$this->status->setErrorFlags( $this->errorType );
			return $this->status;
		}

		// NOTE: Make sure the latest revision is loaded and cached.
		//      Would happen on demand anyway, but we want a well-defined point at which "latest" is
		//      frozen to a specific revision, just before the first check for edit conflicts.
		//      We can use the ID of the latest revision to protect against race conditions:
		//      if getLatestRevision() was called earlier by application logic, saving will fail
		//      if any new revisions were created between then and now.
		//      Note that this protection against "late" conflicts is unrelated to the detection
		//      of edit conflicts during user interaction, which use the base revision supplied
		//      to the constructor.
		try {
			$this->getLatestRevision();
		} catch ( RevisionedUnresolvedRedirectException $exception ) {
			$this->errorType |= EditEntity::PRECONDITION_FAILED_ERROR;
			$this->status->fatal(
				'wikibase-save-unresolved-redirect',
				$exception->getEntityId()->getSerialization(),
				$exception->getRedirectTargetId()->getSerialization()
			);
			$this->status->setErrorFlags( $this->errorType );
			return $this->status;
		}

		$raceProtectionRevId = $this->getLatestRevisionId();

		if ( $raceProtectionRevId === 0 ) {
			$raceProtectionRevId = false;
		}

		if ( $this->hasEditConflict() ) {
			$newEntity = $this->fixEditConflict( $newEntity );

			if ( !$newEntity ) {
				$this->errorType |= EditEntity::EDIT_CONFLICT_ERROR;
				$this->status->setErrorFlags( $this->errorType );
				$this->status->error( 'edit-conflict' );

				return $this->status;
			}
		}

		if ( !$this->status->isOK() ) {
			$this->errorType |= EditEntity::PRECONDITION_FAILED_ERROR;
			$this->status->setErrorFlags( $this->errorType );
			return $this->status;
		}

		$savedTempUser = $this->createTempUserIfNeeded(); // updates $this->user, $this->context and/or $this->status
		if ( !$this->status->isOK() ) {
			$this->status->setErrorFlags( $this->errorType );
			return $this->status;
		}

		$this->checkEditFilter( $newEntity, $summary );
		if ( !$this->status->isOK() ) {
			$this->status->setErrorFlags( $this->errorType );
			return $this->status;
		}

		try {
			$entityRevision = $this->entityStore->saveEntity(
				$newEntity,
				$summary,
				$this->user,
				$flags | EDIT_AUTOSUMMARY,
				$raceProtectionRevId,
				$tags
			);

			$this->entityId = $newEntity->getId();
			$editStatus = EditEntityStatus::newEdit( $entityRevision, $savedTempUser, $this->context );
		} catch ( StorageException $ex ) {
			$editStatus = $ex->getStatus();

			if ( $editStatus === null ) {
				// XXX: perhaps internalerror_info isn't the best, but we need some generic error message.
				$editStatus = Status::newFatal( 'internalerror_info', $ex->getMessage() );
			}

			$this->errorType |= EditEntity::SAVE_ERROR;
		} catch ( EntityContentTooBigException $ex ) {
			$this->status->setErrorFlags( $this->errorType );
			$this->status->error( wfMessage( 'wikibase-error-entity-too-big' )->sizeParams( $this->maxSerializedEntitySize * 1024 ) );
			return $this->status;
		}

		$this->status->setResult( $editStatus->isOK(), $editStatus->getValue() );
		$this->status->merge( $editStatus );

		if ( $this->status->isOK() ) {
			$this->updateWatchlist( $watch );
		} else {
			$this->status->setErrorFlags( $this->errorType );
		}

		return $this->status;
	}

	/**
	 * Check the entity against the {@link EditFilterHookRunner} and update $this->status accordingly.
	 */
	private function checkEditFilter( EntityDocument $newEntity, string $summary ): void {
		try {
			$hookStatus = $this->editFilterHookRunner->run( $newEntity, $this->context, $summary );
		} catch ( EntityContentTooBigException $ex ) {
			$this->status->error( wfMessage( 'wikibase-error-entity-too-big' )->sizeParams( $this->maxSerializedEntitySize * 1024 ) );
			return;
		}
		if ( !$hookStatus->isOK() ) {
			$this->errorType |= EditEntity::FILTERED_ERROR;
		}
		$this->status->merge( $hookStatus );
	}

	/**
	 * If a temp user ought to be created then create and return it, and update $this->user and $this->context.
	 * Also update $this->status with any potential error.
	 * Returns null (and leaves $this->user unmodified, i.e. nonnull) if no temp user is needed.
	 */
	private function createTempUserIfNeeded(): ?User {
		$savedTempUser = null;
		if ( $this->tempUserCreator->shouldAutoCreate( $this->user, EntityPermissionChecker::ACTION_EDIT ) ) {
			$status = $this->tempUserCreator->create( null, $this->context->getRequest() );
			$this->status->merge( $status );
			if ( $status->isOK() ) {
				$savedTempUser = $status->getUser();
				$this->user = $savedTempUser;
				$this->context = new DerivativeContext( $this->context );
				$this->context->setUser( $savedTempUser );
			} else {
				$this->errorType |= self::SAVE_ERROR;
			}
		}
		return $savedTempUser;
	}

	/**
	 * Returns whether the present edit would, per default,
	 * lead to the user watching the page.
	 *
	 * This uses the user's watchdefault and watchcreations settings
	 * and considers whether the entity is already watched by the user.
	 *
	 * @note Keep in sync with logic in \MediaWiki\EditPage\EditPage!
	 */
	private function getWatchDefault(): bool {
		// User wants to watch all edits or all creations.
		if ( $this->userOptionsLookup->getOption( $this->user, 'watchdefault' )
			|| ( $this->userOptionsLookup->getOption( $this->user, 'watchcreations' )
			&& $this->isNewPage() )
		) {
			return true;
		}

		// keep current state
		return $this->getEntityId() !== null &&
			$this->entityStore->isWatching( $this->user, $this->getEntityId() );
	}

	/**
	 * Watches or unwatches the entity.
	 *
	 * @note Keep in sync with logic in \MediaWiki\EditPage\EditPage!
	 * @todo move to separate service
	 *
	 * @param bool $watch whether to watch or unwatch the page.
	 */
	private function updateWatchlist( bool $watch ): void {
		if ( $this->getTitle() === null ) {
			throw new RuntimeException( 'Title not yet known!' );
		}

		$this->entityStore->updateWatchlist( $this->user, $this->getEntityId(), $watch );
	}

}

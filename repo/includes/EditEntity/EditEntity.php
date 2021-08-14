<?php

namespace Wikibase\Repo\EditEntity;

use MWException;
use ReadOnlyError;
use Status;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityRevision;

/**
 * Handler for editing activity, providing a unified interface for saving modified entities while performing
 * permission checks and handling edit conflicts.
 *
 * @license GPL-2.0-or-later
 */
interface EditEntity {

	/**
	 * indicates a permission error
	 */
	public const PERMISSION_ERROR = 1;

	/**
	 * indicates an unresolved edit conflict
	 */
	public const EDIT_CONFLICT_ERROR = 2;

	/**
	 * indicates a token or session error
	 */
	public const TOKEN_ERROR = 4;

	/**
	 * indicates that an error occurred while saving
	 */
	public const SAVE_ERROR = 8;

	/**
	 * Indicates that the content failed some precondition to saving,
	 * such as a global uniqueness constraint.
	 */
	public const PRECONDITION_FAILED = 16;

	/**
	 * Indicates that the content triggered an edit filter that uses
	 * the EditFilterMergedContent hook to supervise edits.
	 */
	public const FILTERED = 32;

	/**
	 * Indicates that the edit exceeded a rate limit.
	 */
	public const RATE_LIMIT = 64;

	/**
	 * bit mask for asking for any error.
	 */
	public const ANY_ERROR = 0xFFFFFFFF;

	/**
	 * Returns the ID of the entity being edited.
	 * May be null if a new entity is to be created.
	 *
	 * @return null|EntityId
	 */
	public function getEntityId();

	/**
	 * Returns the latest revision of the entity.
	 *
	 * @return EntityRevision|null
	 */
	public function getLatestRevision();

	/**
	 * Return the base revision for the edit. If no base revision ID was supplied to
	 * the constructor, this returns the latest revision. If the entity does not exist
	 * yet, this returns null.
	 *
	 * @return EntityRevision|null
	 * @throws MWException
	 */
	public function getBaseRevision();

	/**
	 * Get the status object. Only defined after attemptSave() was called.
	 *
	 * After a successful save, the Status object's value field will contain an array,
	 * just like the status returned by WikiPage::doUserEditContent(). Well known fields
	 * in the status value are:
	 *
	 *  - new: bool whether the edit created a new page
	 *  - revision: Revision the new revision object
	 *  - errorFlags: bit field indicating errors, see the XXX_ERROR constants.
	 *
	 * @return Status
	 */
	public function getStatus();

	/**
	 * Determines whether the last call to attemptSave was successful.
	 *
	 * @return bool false if attemptSave() failed, true otherwise
	 */
	public function isSuccess();

	/**
	 * Checks whether this EditEntity encountered any of the given error types while executing attemptSave().
	 *
	 * @param int $errorType bit field using the EditEntity::XXX_ERROR constants.
	 *            Defaults to EditEntity::ANY_ERROR.
	 *
	 * @return bool true if this EditEntity encountered any of the error types in $errorType, false otherwise.
	 */
	public function hasError( $errorType = self::ANY_ERROR );

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
	public function hasEditConflict();

	/**
	 * Make sure the given WebRequest contains a valid edit token.
	 *
	 * @param string $token The token to check.
	 *
	 * @return bool true if the token is valid
	 */
	public function isTokenOK( $token );

	/**
	 * Attempts to save the given Entity object.
	 *
	 * This method performs entity level permission checks, checks the edit toke, enforces rate
	 * limits, resolves edit conflicts, and updates user watchlists if appropriate.
	 *
	 * Success or failure are reported via the Status object returned by this method.
	 *
	 * @todo $flags here should ideally not refer to EDIT_ constants from mediawiki core.
	 * @todo This shouldn't throw MWExceptions
	 *
	 * @param EntityDocument $newEntity
	 * @param string $summary The edit summary.
	 * @param int $flags The EDIT_XXX flags as used by WikiPage::doUserEditContent().
	 *        Additionally, the EntityContent::EDIT_XXX constants can be used.
	 * @param string|bool $token Edit token to check, or false to disable the token check.
	 *                                Null will fail the token text, as will the empty string.
	 * @param bool|null $watch Whether the user wants to watch the entity.
	 *                                Set to null to apply default according to getWatchDefault().
	 * @param string[] $tags Change tags to add to the edit.
	 * Callers are responsible for checking that the user is permitted to add these tags.
	 *
	 * @return Status
	 *
	 * @throws MWException
	 * @throws ReadOnlyError
	 *
	 * @see    WikiPage::doUserEditContent
	 * @see    EntityStore::saveEntity
	 */
	public function attemptSave(
		EntityDocument $newEntity,
		string $summary,
		$flags,
		$token,
		$watch = null,
		array $tags = []
	);

}

<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Interactors;

use MediaWiki\Context\IContextSource;
use MediaWiki\MediaWikiServices;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\User\User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\FormatableSummary;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\ChangeOp\ChangeOpsMerge;
use Wikibase\Repo\Content\EntityContent;
use Wikibase\Repo\EditEntity\EditEntityStatus;
use Wikibase\Repo\EditEntity\MediaWikiEditEntityFactory;
use Wikibase\Repo\Merge\MergeFactory;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\SummaryFormatter;

/**
 * @license GPL-2.0-or-later
 * @author Addshore
 * @author Daniel Kinzler
 * @author Lucie-Aimée Kaffee
 */
class ItemMergeInteractor {

	private MergeFactory $mergeFactory;
	private EntityRevisionLookup $entityRevisionLookup;
	private MediaWikiEditEntityFactory $editEntityFactory;
	private EntityPermissionChecker $permissionChecker;
	private SummaryFormatter $summaryFormatter;
	private ItemRedirectCreationInteractor $interactorRedirect;
	private EntityTitleStoreLookup $entityTitleLookup;
	private PermissionManager $permissionManager;

	public function __construct(
		MergeFactory $mergeFactory,
		EntityRevisionLookup $entityRevisionLookup,
		MediaWikiEditEntityFactory $editEntityFactory,
		EntityPermissionChecker $permissionChecker,
		SummaryFormatter $summaryFormatter,
		ItemRedirectCreationInteractor $interactorRedirect,
		EntityTitleStoreLookup $entityTitleLookup,
		PermissionManager $permissionManager
	) {
		$this->mergeFactory = $mergeFactory;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->editEntityFactory = $editEntityFactory;
		$this->permissionChecker = $permissionChecker;
		$this->summaryFormatter = $summaryFormatter;
		$this->interactorRedirect = $interactorRedirect;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->permissionManager = $permissionManager;
	}

	/**
	 * Check user's merge permissions for the given entity ID.
	 * (Note that this is not redundant with the check in EditEntity later,
	 * because that checks edit permissions, not merge.)
	 *
	 * @param EntityId $entityId
	 *
	 * @throws ItemMergeException if the permission check fails
	 */
	private function checkPermissions( EntityId $entityId, User $user ): void {
		$status = $this->permissionChecker->getPermissionStatusForEntityId(
			$user,
			EntityPermissionChecker::ACTION_MERGE,
			$entityId
		);

		if ( !$status->isOK() ) {
			// XXX: This is silly, we really want to pass the Status object to the API error handler.
			// Perhaps we should get rid of ItemMergeException and use Status throughout.
			throw new ItemMergeException( $status->getWikiText(), 'permissiondenied' );
		}
	}

	/**
	 * Merges the content of the first item into the second and creates a redirect if the first item
	 * is empty after the merge.
	 *
	 * @param ItemId $fromId
	 * @param ItemId $toId
	 * @param IContextSource $context ContextSource to obtain the user from and pass
	 * 	down to createRedirect
	 * @param string[] $ignoreConflicts The kinds of conflicts to ignore
	 * @param string|null $summary
	 * @param bool $bot Mark the edit as bot edit
	 * @param string[] $tags Change tags to add to the edit.
	 * Callers are responsible for permission checks
	 * (typically using {@link ChangeTags::canAddTagsAccompanyingChange}).
	 *
	 * @return ItemMergeStatus Note that the status is only returned
	 * to wrap the created revisions, context and saved temp user in a strongly typed container.
	 * Errors are (currently) reported as exceptions, not as a failed status.
	 * (It would be nice to fix this at some point and use status consistently.)
	 *
	 * @throws ItemMergeException
	 * @throws RedirectCreationException
	 * @suppress PhanTypeMismatchArgument
	 */
	public function mergeItems(
		ItemId $fromId,
		ItemId $toId,
		IContextSource $context,
		array $ignoreConflicts = [],
		?string $summary = null,
		bool $bot = false,
		array $tags = []
	): ItemMergeStatus {
		$user = $context->getUser();
		$this->checkPermissions( $fromId, $user );
		$this->checkPermissions( $toId, $user );

		/**
		 * @var Item $fromItem
		 * @var Item $toItem
		 */
		$fromItem = $this->loadEntity( $fromId );
		$toItem = $this->loadEntity( $toId );

		$this->validateEntities( $fromItem, $toItem );

		// strip any bad values from $ignoreConflicts
		$ignoreConflicts = array_intersect( $ignoreConflicts, ChangeOpsMerge::CONFLICT_TYPES );

		try {
			$changeOps = $this->mergeFactory->newMergeOps(
				$fromItem,
				$toItem,
				$ignoreConflicts
			);

			$changeOps->apply();
		} catch ( ChangeOpException $e ) {
			throw new ItemMergeException( $e->getMessage(), 'failed-modify', $e );
		}

		$mergeStatus = $this->attemptSaveMerge( $fromItem, $toItem, $summary, $context, $bot, $tags );
		$context = $mergeStatus->getContext();
		$this->updateWatchlistEntries( $fromId, $toId );

		$redirected = false;
		$redirectSavedTempUser = null;

		if ( $this->isEmpty( $fromId ) ) {
			$redirectStatus = $this->interactorRedirect->createRedirect( $fromId, $toId, $bot, $tags, $context );
			$context = $redirectStatus->getContext();
			$redirectSavedTempUser = $redirectStatus->getSavedTempUser();
			$redirected = true;
		}

		return ItemMergeStatus::newMerge(
			$mergeStatus->getFromEntityRevision(),
			$mergeStatus->getToEntityRevision(),
			$mergeStatus->getSavedTempUser() ?? $redirectSavedTempUser,
			$context,
			$redirected
		);
	}

	private function isEmpty( ItemId $itemId ): bool {
		return $this->loadEntity( $itemId )->isEmpty();
	}

	/**
	 * Either throws an exception or returns a EntityDocument object.
	 *
	 * @param ItemId $itemId
	 *
	 * @return EntityDocument
	 * @throws ItemMergeException
	 */
	private function loadEntity( ItemId $itemId ): EntityDocument {
		try {
			$revision = $this->entityRevisionLookup->getEntityRevision(
				$itemId,
				0,
				 LookupConstants::LATEST_FROM_MASTER
			);

			if ( !$revision ) {
				throw new ItemMergeException(
					"Entity $itemId not found",
					'no-such-entity'
				);
			}

			return $revision->getEntity();
		} catch ( StorageException | RevisionedUnresolvedRedirectException $ex ) {
			throw new ItemMergeException( $ex->getMessage(), 'cant-load-entity-content', $ex );
		}
	}

	/**
	 * @param EntityDocument $fromEntity
	 * @param EntityDocument $toEntity
	 *
	 * @throws ItemMergeException
	 */
	private function validateEntities( EntityDocument $fromEntity, EntityDocument $toEntity ): void {
		if ( !( $fromEntity instanceof Item && $toEntity instanceof Item ) ) {
			throw new ItemMergeException( 'One or more of the entities are not items', 'not-item' );
		}

		if ( $toEntity->getId()->equals( $fromEntity->getId() ) ) {
			throw new ItemMergeException( 'You must provide unique ids', 'cant-merge-self' );
		}
	}

	/**
	 * @param string $direction either 'from' or 'to'
	 * @param ItemId $getId
	 * @param string|null $customSummary
	 *
	 * @return Summary
	 */
	private function getSummary( string $direction, ItemId $getId, ?string $customSummary = null ): Summary {
		$summary = new Summary( 'wbmergeitems', $direction, null, [ $getId->getSerialization() ] );
		if ( $customSummary !== null ) {
			$summary->setUserSummary( $customSummary );
		}
		return $summary;
	}

	/**
	 * @param Item $fromItem
	 * @param Item $toItem
	 * @param string|null $summary
	 * @param IContextSource $context
	 * @param bool $bot
	 * @param string[] $tags
	 *
	 * @return ItemMergeStatus but with the 'redirected' member missing (to be added by the caller)
	 */
	private function attemptSaveMerge(
		Item $fromItem,
		Item $toItem,
		?string $summary,
		IContextSource $context,
		bool $bot,
		array $tags
	): ItemMergeStatus {
		// Note: the edits and summaries are potentially confusing;
		// on the “from” item, we use the summary “Merged Item *into*” and mention the “to” item ID;
		// on the “to” item, we use the summary “Merged item *from*” and mention the “from” item ID.

		$fromSummary = $this->getSummary( 'to', $toItem->getId(), $summary );
		$fromStatus = $this->saveItem( $fromItem, $fromSummary, $context, $bot, $tags );
		$context = $fromStatus->getContext();

		$toSummary = $this->getSummary( 'from', $fromItem->getId(), $summary );
		$toStatus = $this->saveItem( $toItem, $toSummary, $context, $bot, $tags );
		$context = $toStatus->getContext();

		return ItemMergeStatus::newMerge(
			$fromStatus->getRevision(),
			$toStatus->getRevision(),
			$fromStatus->getSavedTempUser() ?? $toStatus->getSavedTempUser(),
			$context
		);
	}

	private function saveItem(
		Item $item,
		FormatableSummary $summary,
		IContextSource $context,
		bool $bot,
		array $tags
	): EditEntityStatus {
		// Given we already check all constraints in ChangeOpsMerge, it's
		// fine to ignore them here. This is also needed to not run into
		// the constraints we're supposed to ignore (see ChangeOpsMerge::removeConflictsWithEntity
		// for reference)
		$flags = EDIT_UPDATE | EntityContent::EDIT_IGNORE_CONSTRAINTS;
		if ( $bot && $this->permissionManager->userHasRight( $context->getUser(), 'bot' ) ) {
			$flags |= EDIT_FORCE_BOT;
		}

		$formattedSummary = $this->summaryFormatter->formatSummary( $summary );

		$editEntity = $this->editEntityFactory->newEditEntity( $context, $item->getId() );
		$status = $editEntity->attemptSave(
			$item,
			$formattedSummary,
			$flags,
			false,
			null,
			$tags
		);
		if ( !$status->isOK() ) {
			// as in checkPermissions() above, it would be better to just pass the Status to the API
			throw new ItemMergeException( $status->getWikiText(), 'failed-save' );
		}

		return $status;
	}

	private function updateWatchlistEntries( ItemId $fromId, ItemId $toId ): void {
		$fromTitle = $this->entityTitleLookup->getTitleForId( $fromId );
		$toTitle = $this->entityTitleLookup->getTitleForId( $toId );

		MediaWikiServices::getInstance()
			->getWatchedItemStore()
			->duplicateAllAssociatedEntries( $fromTitle, $toTitle );
	}

}

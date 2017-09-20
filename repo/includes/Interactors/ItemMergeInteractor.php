<?php

namespace Wikibase\Repo\Interactors;

use MediaWiki\MediaWikiServices;
use User;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\ChangeOp\ChangeOpsMerge;
use Wikibase\Repo\ChangeOp\MergeChangeOpsFactory;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityContent;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Summary;
use Wikibase\Lib\FormatableSummary;
use Wikibase\SummaryFormatter;

/**
 * @license GPL-2.0+
 * @author Addshore
 * @author Daniel Kinzler
 * @author Lucie-Aimée Kaffee
 */
class ItemMergeInteractor {

	/**
	 * @var MergeChangeOpsFactory
	 */
	private $changeOpFactory;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var EntityStore
	 */
	private $entityStore;

	/**
	 * @var EntityPermissionChecker
	 */
	private $permissionChecker;

	/**
	 * @var SummaryFormatter
	 */
	private $summaryFormatter;

	/**
	 * @var User
	 */
	private $user;

	/**
	* @var RedirectCreationInteractor
	*/
	private $interactorRedirect;

	/**
	 * @var EntityTitleStoreLookup
	 */
	private $entityTitleLookup;

	public function __construct(
		MergeChangeOpsFactory $changeOpFactory,
		EntityRevisionLookup $entityRevisionLookup,
		EntityStore $entityStore,
		EntityPermissionChecker $permissionChecker,
		SummaryFormatter $summaryFormatter,
		User $user,
		RedirectCreationInteractor $interactorRedirect,
		EntityTitleStoreLookup $entityTitleLookup
	) {
		$this->changeOpFactory = $changeOpFactory;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->entityStore = $entityStore;
		$this->permissionChecker = $permissionChecker;
		$this->summaryFormatter = $summaryFormatter;
		$this->user = $user;
		$this->interactorRedirect = $interactorRedirect;
		$this->entityTitleLookup = $entityTitleLookup;
	}

	/**
	 * Check user's for the given entity ID.
	 *
	 * @param EntityId $entityId
	 *
	 * @throws ItemMergeException if the permission check fails
	 */
	private function checkPermissions( EntityId $entityId ) {
		$status = $this->permissionChecker->getPermissionStatusForEntityId(
			$this->user,
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
	 * @param string[] $ignoreConflicts The kinds of conflicts to ignore
	 * @param string|null $summary
	 * @param bool $bot Mark the edit as bot edit
	 *
	 * @return array A list of exactly two EntityRevision objects and a boolean. The first
	 *  EntityRevision object represents the modified source item, the second one represents the
	 *  modified target item. The boolean indicates whether the redirect was successful.
	 *
	 * @throws ItemMergeException
	 */
	public function mergeItems(
		ItemId $fromId,
		ItemId $toId,
		array $ignoreConflicts = [],
		$summary = null,
		$bot = false
	) {
		$this->checkPermissions( $fromId );
		$this->checkPermissions( $toId );

		/**
		 * @var Item $fromItem
		 * @var Item $toItem
		 */
		$fromItem = $this->loadEntity( $fromId );
		$toItem = $this->loadEntity( $toId );

		$this->validateEntities( $fromItem, $toItem );

		// strip any bad values from $ignoreConflicts
		$ignoreConflicts = array_intersect( $ignoreConflicts, ChangeOpsMerge::$conflictTypes );

		try {
			$changeOps = $this->changeOpFactory->newMergeOps(
				$fromItem,
				$toItem,
				$ignoreConflicts
			);

			$changeOps->apply();
		} catch ( ChangeOpException $e ) {
			throw new ItemMergeException( $e->getMessage(), 'failed-modify', $e );
		}

		$result = $this->attemptSaveMerge( $fromItem, $toItem, $summary, $bot );
		$this->updateWatchlistEntries( $fromId, $toId );

		$redirected = false;

		if ( $this->isEmpty( $fromId ) ) {
			$this->interactorRedirect->createRedirect( $fromId, $toId, $bot );
			$redirected = true;
		}

		array_push( $result, $redirected );
		return $result;
	}

	/**
	 * @param ItemId $itemId
	 *
	 * @return bool
	 */
	private function isEmpty( ItemId $itemId ) {
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
	private function loadEntity( ItemId $itemId ) {
		try {
			$revision = $this->entityRevisionLookup->getEntityRevision(
				$itemId,
				0,
				EntityRevisionLookup::LATEST_FROM_MASTER
			);

			if ( !$revision ) {
				throw new ItemMergeException(
					"Entity $itemId not found",
					'no-such-entity'
				);
			}

			return $revision->getEntity();
		} catch ( StorageException $ex ) {
			throw new ItemMergeException( $ex->getMessage(), 'cant-load-entity-content', $ex );
		} catch ( RevisionedUnresolvedRedirectException $ex ) {
			throw new ItemMergeException( $ex->getMessage(), 'cant-load-entity-content', $ex );
		}
	}

	/**
	 * @param EntityDocument $fromEntity
	 * @param EntityDocument $toEntity
	 *
	 * @throws ItemMergeException
	 */
	private function validateEntities( EntityDocument $fromEntity, EntityDocument $toEntity ) {
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
	private function getSummary( $direction, ItemId $getId, $customSummary = null ) {
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
	 * @param bool $bot
	 *
	 * @return array A list of exactly two EntityRevision objects. The first one represents the
	 *  modified source item, the second one represents the modified target item.
	 */
	private function attemptSaveMerge( Item $fromItem, Item $toItem, $summary, $bot ) {
		$toSummary = $this->getSummary( 'to', $toItem->getId(), $summary );
		$fromRev = $this->saveItem( $fromItem, $toSummary, $bot );

		$fromSummary = $this->getSummary( 'from', $fromItem->getId(), $summary );
		$toRev = $this->saveItem( $toItem, $fromSummary, $bot );

		return [ $fromRev, $toRev ];
	}

	private function saveItem( Item $item, FormatableSummary $summary, $bot ) {
		// Given we already check all constraints in ChangeOpsMerge, it's
		// fine to ignore them here. This is also needed to not run into
		// the constraints we're supposed to ignore (see ChangeOpsMerge::removeConflictsWithEntity
		// for reference)
		$flags = EDIT_UPDATE | EntityContent::EDIT_IGNORE_CONSTRAINTS;
		if ( $bot && $this->user->isAllowed( 'bot' ) ) {
			$flags |= EDIT_FORCE_BOT;
		}

		try {
			return $this->entityStore->saveEntity(
				$item,
				$this->summaryFormatter->formatSummary( $summary ),
				$this->user,
				$flags
			);
		} catch ( StorageException $ex ) {
			throw new ItemMergeException( $ex->getMessage(), 'failed-save', $ex );
		}
	}

	private function updateWatchlistEntries( ItemId $fromId, ItemId $toId ) {
		$fromTitle = $this->entityTitleLookup->getTitleForId( $fromId );
		$toTitle = $this->entityTitleLookup->getTitleForId( $toId );

		MediaWikiServices::getInstance()
			->getWatchedItemStore()
			->duplicateAllAssociatedEntries( $fromTitle, $toTitle );
	}

}

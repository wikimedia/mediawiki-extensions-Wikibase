<?php

namespace Wikibase\Repo\Interactors;

use User;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\ChangeOp\ChangeOpsMerge;
use Wikibase\ChangeOp\MergeChangeOpsFactory;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;
use Wikibase\Repo\Interactors\RedirectCreationInteractor;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Daniel Kinzler
 * @author Lucie-Aimée Kaffee
 *
 * @todo allow merging of specific parts of an item only (eg. sitelinks,aliases,claims)
 * @todo allow optional redirect creation after merging
 */
class ItemMergeInteractor {

	/**
	 * @var MergeChangeOpsFactory
	 */
	protected $changeOpFactory;

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
	 * @param MergeChangeOpsFactory $changeOpFactory
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param EntityStore $entityStore
	 * @param EntityPermissionChecker $permissionChecker
	 * @param SummaryFormatter $summaryFormatter
	 * @param User $user
	 */
	public function __construct(
		MergeChangeOpsFactory $changeOpFactory,
		EntityRevisionLookup $entityRevisionLookup,
		EntityStore $entityStore,
		EntityPermissionChecker $permissionChecker,
		SummaryFormatter $summaryFormatter,
		User $user,
		RedirectCreationInteractor $interactorRedirect
	) {

		$this->changeOpFactory = $changeOpFactory;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->entityStore = $entityStore;
		$this->permissionChecker = $permissionChecker;
		$this->summaryFormatter = $summaryFormatter;
		$this->user = $user;
		$this->interactorRedirect = $interactorRedirect;
	}

	/**
	 * Check all applicable permissions for redirecting the given $entityId.
	 *
	 * @param EntityId $entityId
	 */
	private function checkPermissions( EntityId $entityId ) {
		$permissions = array(
			'edit',
			$entityId->getEntityType() . '-merge'
		);

		foreach ( $permissions as $permission ) {
			$this->checkPermission( $entityId, $permission );
		}
	}

	/**
	 * Check the given permissions for the given $entityId.
	 *
	 * @param EntityId $entityId
	 * @param $permission
	 *
	 * @throws ItemMergeException if the permission check fails
	 */
	private function checkPermission( EntityId $entityId, $permission ) {
		$status = $this->permissionChecker->getPermissionStatusForEntityId( $this->user, $permission, $entityId );

		if ( !$status->isOK() ) {
			// XXX: This is silly, we really want to pass the Status object to the API error handler.
			// Perhaps we should get rid of ItemMergeException and use Status throughout.
			throw new ItemMergeException( $status->getWikiText(), 'permissiondenied' );
		}
	}

	/**
	 * Merges the content of the first item into the second and creates a redirect if the first item is empty after the merge.
	 *
	 * @param ItemId $fromId
	 * @param ItemId $toId
	 * @param array $ignoreConflicts The kinds of conflicts to ignore
	 * @param string|null $summary
	 * @param bool $bot Mark the edit as bot edit
	 *
	 * @return array A list of exactly two EntityRevision objects. The first one represents
	 * the modified source item, the second one represents the modified target item.
	 *
	 * @throws ItemMergeException
	 */
	public function mergeItems( ItemId $fromId, ItemId $toId, $ignoreConflicts = array(), $summary = null, $bot = false ) {
		$this->checkPermissions( $fromId );
		$this->checkPermissions( $toId );

		$fromEntity = $this->loadEntity( $fromId );
		$toEntity = $this->loadEntity( $toId );

		$this->validateEntities( $fromEntity, $toEntity );

		// strip any bad values from $ignoreConflicts
		$ignoreConflicts = array_intersect( $ignoreConflicts, ChangeOpsMerge::$conflictTypes );

		try {
			$changeOps = $this->changeOpFactory->newMergeOps(
				$fromEntity,
				$toEntity,
				$ignoreConflicts
			);

			$changeOps->apply();
		} catch ( ChangeOpException $e ) {
			throw new ItemMergeException( $e->getMessage(), 'failed-modify', $e );
		}
		
		$result = $this->attemptSaveMerge( $fromEntity, $toEntity, $summary, $bot );
		
		$redirected = false;
		
		if ( $this->checkEmpty( $fromId ) ) {
			$this->createRedirect( $fromId, $toId, $bot );
			$redirected = true;
		}
		
		array_push( $result, $redirected );
		return $result;
	}

	/**
	 * @param ItemId $fromId
	 * @param ItemId $toId
	 * @param bool $bot
	 */
	private function createRedirect( ItemId $fromId, ItemId $toId, $bot ) {
		$this->interactorRedirect->createRedirect( $fromId, $toId, $bot );
	}

	/**
	 * EntityId $entityId
	 */
	private function checkEmpty( EntityId $entityId ) {
		return $this->loadEntity( $entityId )->isEmpty();
	}

	private function loadEntity( EntityId $entityId ) {
		try {
			$revision = $this->entityRevisionLookup->getEntityRevision( $entityId, EntityRevisionLookup::LATEST_FROM_MASTER );

			if ( !$revision ) {
				throw new ItemMergeException(
					"Entity $entityId not found",
					'no-such-entity'
				);
			}

			return $revision->getEntity();
		} catch ( StorageException $ex ) {
			throw new ItemMergeException( $ex->getMessage(), 'cant-load-entity-content', $ex );
		}
	}

	/**
	 * @param EntityDocument $fromEntity
	 * @param EntityDocument $toEntity
	 * @throws ItemMergeException
	 */
	private function validateEntities( EntityDocument $fromEntity, EntityDocument $toEntity ) {
		if ( !( $fromEntity instanceof Item && $toEntity instanceof Item ) ) {
			throw new ItemMergeException( 'One or more of the entities are not items', 'not-item' );
		}

		if ( $toEntity->getId()->equals( $fromEntity->getId() ) ) {
			throw new ItemMergeException( 'You must provide unique ids' , 'cant-merge-self' );
		}
	}

	/**
	 * @param string $direction either 'from' or 'to'
	 * @param ItemId $getId
	 * @param string|null $customSummary
	 * @return Summary
	 */
	private function getSummary( $direction, $getId, $customSummary = null ) {
		$summary = new Summary( 'wbmergeitems', $direction, null, array( $getId->getSerialization() ) );
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
	 * @param bool $redirected
	 *
	 * @return array A list of exactly two EntityRevision objects and a boolean for the status of the redirect.
	 * The first one represents the modified source item, the second one represents the modified target item.
	 */
	private function attemptSaveMerge( Item $fromItem, Item $toItem, $summary, $bot ) {
		$toSummary = $this->getSummary( 'to', $toItem->getId(), $summary );
		$fromRev = $this->saveEntity( $fromItem, $toSummary, $bot );

		$fromSummary = $this->getSummary( 'from', $fromItem->getId(), $summary );
		$toRev = $this->saveEntity( $toItem, $fromSummary, $bot );

		return array( $fromRev, $toRev );
	}

	private function saveEntity( Entity $entity, Summary $summary, $bot ) {
		$flags = EDIT_UPDATE;
		if ( $bot && $this->user->isAllowed( 'bot' ) ) {
			$flags |= EDIT_FORCE_BOT;
		}

		try {
			return $this->entityStore->saveEntity(
				$entity,
				$this->summaryFormatter->formatSummary( $summary ),
				$this->user,
				$flags
			);
		} catch ( StorageException $ex ) {
			throw new ItemMergeException( $ex->getMessage(), 'failed-save', $ex );
		}
	}

}

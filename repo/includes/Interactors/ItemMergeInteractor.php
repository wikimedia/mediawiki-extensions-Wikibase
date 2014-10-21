<?php

namespace Wikibase\Repo\Interactors;

use User;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\ChangeOp\ChangeOpsMerge;
use Wikibase\ChangeOp\MergeChangeOpsFactory;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Daniel Kinzler
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
		User $user
	) {

		$this->changeOpFactory = $changeOpFactory;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->entityStore = $entityStore;
		$this->permissionChecker = $permissionChecker;
		$this->summaryFormatter = $summaryFormatter;
		$this->user = $user;
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
	 * Merges the content of the first item into the second.
	 *
	 * @param ItemId $fromId
	 * @param ItemId $toId
	 * @param array $ignoreConflicts The kinds of conflicts to ignore
	 * @param string|null $summary
	 *
	 * @return array A list of exactly two EntityRevision objects. The first one represents
	 * the modified source item, the second one represents the modified target item.
	 *
	 * @throws ItemMergeException
	 */
	public function mergeItems( ItemId $fromId, ItemId $toId, $ignoreConflicts = array(), $summary = null ) {

		$this->checkPermissions( $fromId );
		$this->checkPermissions( $toId );

		$fromEntity = $this->loadEntity( $fromId );
		$toEntity = $this->loadEntity( $toId );

		$this->validateEntities( $fromEntity, $toEntity );

		// strip any bad values from $ignoreConflicts
		$ignoreConflicts = array_intersect( $ignoreConflicts, ChangeOpsMerge::$conflictTypes );

		try{
			$changeOps = $this->changeOpFactory->newMergeOps(
				$fromEntity,
				$toEntity,
				$ignoreConflicts
			);

			$changeOps->apply();
		}
		catch( ChangeOpException $e ) {
			throw new ItemMergeException( $e->getMessage(), 'failed-modify', $e );
		}

		return $this->attemptSaveMerge( $fromEntity, $toEntity, $summary );
	}

	private function loadEntity( EntityId $entityId ) {
		try {
			$revision = $this->entityRevisionLookup->getEntityRevision( $entityId );

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

	private function validateEntities( Entity $fromEntity, Entity $toEntity ) {
		if ( !( $fromEntity instanceof Item && $toEntity instanceof Item ) ) {
			throw new ItemMergeException( 'One or more of the entities are not items', 'not-item' );
		}

		if( $toEntity->getId()->equals( $fromEntity->getId() ) ){
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
	 *
	 * @return array A list of exactly two EntityRevision objects. The first one represents
	 * the modified source item, the second one represents the modified target item.
	 */
	private function attemptSaveMerge( Item $fromItem, Item $toItem, $summary = null ) {
		$toSummary = $this->getSummary( 'to', $toItem->getId(), $summary );
		$fromRev = $this->saveEntity( $fromItem, $toSummary );

		$fromSummary = $this->getSummary( 'from', $fromItem->getId(), $summary );
		$toRev = $this->saveEntity( $toItem, $fromSummary );

		return array( $fromRev, $toRev );
	}

	private function saveEntity( Entity $entity, Summary $summary ) {
		try {
			return $this->entityStore->saveEntity(
				$entity,
				$this->summaryFormatter->formatSummary( $summary ),
				$this->user,
				EDIT_UPDATE
			);
		} catch ( StorageException $ex ) {
			throw new ItemMergeException( $ex->getMessage(), 'failed-save', $ex );
		}
	}

}

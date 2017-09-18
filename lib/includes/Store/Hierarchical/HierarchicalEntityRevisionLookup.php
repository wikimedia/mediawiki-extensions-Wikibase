<?php

namespace Wikibase\Lib\Store\Hierarchical;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\StorageException;

/**
 * Wrapper for EntityRevisionLookups for resolving entity IDs that use a hierarchical addressing
 * scheme.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class HierarchicalEntityRevisionLookup implements EntityRevisionLookup {

	/**
	 * @var EntityRevisionLookup
	 */
	private $lookup;

	public function __construct( EntityRevisionLookup $lookup ) {
		$this->lookup = $lookup;
	}

	/**
	 * @see EntityRevisionLookup::getEntityRevision
	 *
	 * This implementation resolves one, and only one level of HierarchicalEntityIds.
	 *
	 * @param EntityId $entityId
	 * @param int $revisionId
	 * @param string $mode
	 *
	 * @throws RevisionedUnresolvedRedirectException
	 * @throws StorageException
	 * @return EntityRevision|null
	 */
	public function getEntityRevision(
		EntityId $entityId,
		$revisionId = 0,
		$mode = self::LATEST_FROM_REPLICA
	) {
		if ( !( $entityId instanceof HierarchicalEntityId ) ) {
			return $this->lookup->getEntityRevision( $entityId, $revisionId, $mode );
		}

		$parentId = $entityId->getParentId();
		$parentRevision = $this->lookup->getEntityRevision( $parentId, $revisionId, $mode );
		if ( $parentRevision === null ) {
			return null;
		}

		/** @var HierarchicalEntityContainer $parent */
		$parent = $parentRevision->getEntity();

		try {
			$child = $parent->getChildEntity( $entityId );
		} catch ( OutOfBoundsException $ex ) {
			return null;
		}

		return new EntityRevision(
			$child,
			$parentRevision->getRevisionId(),
			$parentRevision->getTimestamp()
		);
	}

	/**
	 * @see EntityRevisionLookup::getLatestRevisionId
	 *
	 * This implementation resolves one, and only one level of HierarchicalEntityIds.
	 *
	 * @param EntityId $entityId
	 * @param string $mode
	 *
	 * @return int|false
	 */
	public function getLatestRevisionId( EntityId $entityId, $mode = self::LATEST_FROM_REPLICA ) {
		if ( $entityId instanceof HierarchicalEntityId ) {
			$entityId = $entityId->getParentId();
		}

		return $this->lookup->getLatestRevisionId( $entityId, $mode );
	}

}

<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityContainer;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\HierarchicalEntityId;

/**
 * Generic implementation of EntityRevisionLookup for resolving EntityIds that
 * use a hierarchical addressing scheme.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class HierarchicalEntityRevisionLookup implements EntityRevisionLookup {

	/**
	 * @var EntityRevisionLookup
	 */
	private $lookup;

	/**
	 * @param EntityRevisionLookup $lookup
	 */
	public function __construct( EntityRevisionLookup $lookup ) {
		$this->lookup = $lookup;
	}

	/**
	 * @see EntityRevisionLookup::getEntityRevision.
	 *
	 * This implementation recursively resolves HierarchicalEntityIds.
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

		$baseId = $entityId->getBaseId();
		$containerRevision = $this->getEntityRevision( $baseId, $revisionId, $mode );

		if ( $containerRevision === null ) {
			return null;
		}

		$container = $containerRevision->getEntity();

		if ( !( $container instanceof  EntityContainer) ) {
			throw new StorageException( 'Cannot resolve ID ' . $entityId );
		}

		$entity = $container->getEntity( $entityId );

		return new EntityRevision(
			$entity,
			$containerRevision->getRevisionId(),
			$containerRevision->getTimestamp()
		);
	}

	/**
	 * @see EntityRevisionLookup::getLatestRevisionId.
	 *
	 * This implementation finds the root of any hierarchical EntityId before looking up the
	 * latest revision.
	 *
	 * @param EntityId $entityId
	 * @param string $mode LATEST_FROM_REPLICA, LATEST_FROM_REPLICA_WITH_FALLBACK or LATEST_FROM_MASTER.
	 *        LATEST_FROM_MASTER would force the revision to be determined from the canonical master database.
	 *
	 * @return int|false Returns false in case the entity doesn't exist (this includes redirects).
	 */
	public function getLatestRevisionId( EntityId $entityId, $mode = self::LATEST_FROM_REPLICA ) {
		if ( $entityId instanceof HierarchicalEntityId ) {
			$entityId = $entityId->getRootId();
		}

		return $this->lookup->getLatestRevisionId( $entityId, $mode );
	}

}

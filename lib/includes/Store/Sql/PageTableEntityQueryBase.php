<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Store\Sql;

use MediaWiki\Revision\SlotRecord;
use MediaWiki\Storage\NameTableAccessException;
use MediaWiki\Storage\NameTableStore;
use stdClass;
use Traversable;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikimedia\Rdbms\IReadableDatabase;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * Abstract PageTableEntityQuery implementation allowing simple mapping between rows and entity IDs
 * using one or more fields and some simple logic.
 *
 * @license GPL-2.0-or-later
 */
abstract class PageTableEntityQueryBase implements PageTableEntityQuery {

	private EntityNamespaceLookup $entityNamespaceLookup;

	private NameTableStore $slotRoleStore;

	public function __construct(
		EntityNamespaceLookup $entityNamespaceLookup,
		NameTableStore $slotRoleStore
	) {
		$this->entityNamespaceLookup = $entityNamespaceLookup;
		$this->slotRoleStore = $slotRoleStore;
	}

	/**
	 * @param array $fields Fields to select
	 * @param array|null $revisionJoinConds If non-null, perform an INNER JOIN
	 * against the revision table on these join conditions.
	 * @param EntityId[] $entityIds EntityIds to select
	 * @param IReadableDatabase $db DB to query on
	 * @return stdClass[] Array of rows with keys of their entity ID serializations
	 */
	public function selectRows(
		array $fields,
		?array $revisionJoinConds,
		array $entityIds,
		IReadableDatabase $db
	): array {
		$queryBuilder = $db->newSelectQueryBuilder()
			->select( $fields )
			->select( $this->getFieldsNeededForMapping() )
			->from( 'page' );
		if ( $revisionJoinConds !== null ) {
			$queryBuilder->join( 'revision', null, $revisionJoinConds );
		}
		$this->updateQueryBuilder( $queryBuilder, $entityIds, $db );

		$res = $queryBuilder->caller( __METHOD__ )->fetchResultSet();

		return $this->indexRowsByEntityId( $res );
	}

	/**
	 * @param SelectQueryBuilder $queryBuilder
	 * @param EntityId[] $entityIds
	 * @param IReadableDatabase $db
	 */
	private function updateQueryBuilder( SelectQueryBuilder $queryBuilder, array $entityIds, IReadableDatabase $db ) {
		$where = [];
		$joinSlotsTable = false;

		foreach ( $entityIds as $entityId ) {
			$entityType = $entityId->getEntityType();
			$slotRole = $this->entityNamespaceLookup->getEntitySlotRole( $entityType );
			$namespace = $this->entityNamespaceLookup->getEntityNamespace( $entityType );

			if ( $namespace === null ) {
				throw new EntityLookupException(
					$entityId,
					"No namespace configured for entity type `$entityType`"
				);
			}

			$conditions = $this->getConditionForEntityId( $entityId );
			$conditions['page_namespace'] = $namespace;

			/**
			 * Only check against the slot role when we are not using the main slot.
			 * If we are using the main slot, then we only need to check that the page
			 * exists rather than a specific slot within the page.
			 * This ensures comparability with the pre MCR schema as long as only the
			 * main slot is used.
			 */
			if ( $slotRole !== SlotRecord::MAIN ) {
				try {
					$slotRoleId = $this->slotRoleStore->getId( $slotRole );
				} catch ( NameTableAccessException $e ) {
					// The slot role is not yet saved, nothing to retrieve.
					continue;
				}

				$conditions['slot_role_id'] = $slotRoleId;
				$joinSlotsTable = true;
			}

			$where[] = $db->makeList(
				$conditions,
				LIST_AND
			);
		}

		if ( empty( $where ) ) {
			// If we skipped all entity IDs, select nothing, not everything.
			$queryBuilder->where( '0=1' );
			return;
		}

		if ( $joinSlotsTable ) {
			$usesRevisionTable = in_array( 'revision', $queryBuilder->getQueryInfo()['tables'], true );
			$slotJoinConds = $usesRevisionTable ? 'rev_id=slot_revision_id' : 'page_latest=slot_revision_id';
			$queryBuilder->join( 'slots', null, $slotJoinConds );
		}

		$queryBuilder->where( $db->makeList( $where, LIST_OR ) );
	}

	/**
	 * @param Traversable $rows
	 * @return stdClass[] Array of rows with keys of their entity ID serializations
	 */
	private function indexRowsByEntityId( Traversable $rows ): array {
		$indexedRows = [];

		foreach ( $rows as $row ) {
			$indexedRows[$this->getEntityIdStringFromRow( $row )] = $row;
		}

		return $indexedRows;
	}

	/**
	 * @param EntityId $entityId
	 * @return array SQL condition
	 */
	abstract protected function getConditionForEntityId( EntityId $entityId );

	/**
	 * @param stdClass $row
	 * @return string serialization of an entity ID for example Q123
	 */
	abstract protected function getEntityIdStringFromRow( stdClass $row );

	/**
	 * @return string[] Extra fields needed for the mapping done in mapRowsToEntityIds
	 */
	abstract protected function getFieldsNeededForMapping();

}

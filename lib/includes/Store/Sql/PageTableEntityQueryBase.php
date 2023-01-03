<?php

namespace Wikibase\Lib\Store\Sql;

use MediaWiki\Revision\SlotRecord;
use MediaWiki\Storage\NameTableAccessException;
use MediaWiki\Storage\NameTableStore;
use stdClass;
use Traversable;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikimedia\Rdbms\IDatabase;

/**
 * Abstract PageTableEntityQuery implementation allowing simple mapping between rows and entity IDs
 * using one or more fields and some simple logic.
 *
 * @license GPL-2.0-or-later
 */
abstract class PageTableEntityQueryBase implements PageTableEntityQuery {

	/**
	 * @var EntityNamespaceLookup
	 */
	private $entityNamespaceLookup;

	/**
	 * @var NameTableStore
	 */
	private $slotRoleStore;

	public function __construct(
		EntityNamespaceLookup $entityNamespaceLookup,
		NameTableStore $slotRoleStore
	) {
		$this->entityNamespaceLookup = $entityNamespaceLookup;
		$this->slotRoleStore = $slotRoleStore;
	}

	/**
	 * @param array $fields Fields to select
	 * @param array $joins Joins to use, Keys must be table names.
	 * @param EntityId[] $entityIds EntityIds to select
	 * @param IDatabase $db DB to query on
	 * @return stdClass[] Array of rows with keys of their entity ID serializations
	 */
	public function selectRows(
		array $fields,
		array $joins,
		array $entityIds,
		IDatabase $db
	) {
		$usesRevisionTable = array_key_exists( 'revision', $joins );
		list( $where, $slotJoinConds ) = $this->getQueryInfo( $entityIds, $usesRevisionTable, $db );
		$joins = array_merge( $joins, $slotJoinConds );
		$vars = array_merge( $fields, $this->getFieldsNeededForMapping() );
		$table = array_merge( [ 'page' ], array_keys( $joins ) );

		$res = $db->select(
			$table,
			$vars,
			$where,
			__METHOD__,
			[],
			$joins
		);

		return $this->indexRowsByEntityId( $res );
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param bool $usesRevisionTable
	 * @param IDatabase $db
	 * @return array [ string $whereCondition, array $extraTables ]
	 */
	private function getQueryInfo( array $entityIds, bool $usesRevisionTable, IDatabase $db ) {
		$where = [];
		$slotJoinConds = [];

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
				if ( $usesRevisionTable ) {
					$slotJoinConds = [ 'slots' => [ 'INNER JOIN', 'rev_id=slot_revision_id' ] ];
				} else {
					$slotJoinConds = [ 'slots' => [ 'INNER JOIN', 'page_latest=slot_revision_id' ] ];
				}
			}

			$where[] = $db->makeList(
				$conditions,
				LIST_AND
			);
		}

		if ( empty( $where ) ) {
			// If we skipped all entity IDs, select nothing, not everything.
			return [ '0=1', [] ];
		}

		return [ $db->makeList( $where, LIST_OR ), $slotJoinConds ];
	}

	/**
	 * @param Traversable $rows
	 * @return stdClass[] Array of rows with keys of their entity ID serializations
	 */
	private function indexRowsByEntityId( Traversable $rows ) {
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

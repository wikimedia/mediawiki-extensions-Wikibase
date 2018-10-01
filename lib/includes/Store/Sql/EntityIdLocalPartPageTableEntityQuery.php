<?php

namespace Wikibase\Lib\Store\Sql;

use MediaWiki\Storage\NameTableAccessException;
use MediaWiki\Storage\NameTableStore;
use Traversable;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikimedia\Rdbms\IDatabase;

/**
 * PageTableEntityQuery that assumes the entity IDs "localPart" matches page_title of the page
 * that the entity is stored on.
 *
 * For example: An Item with ID Q1 is commonly stored on a wikipage with title Q1
 *
 * @license GPL-2.0-or-later
 */
class EntityIdLocalPartPageTableEntityQuery implements PageTableEntityQuery {

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
	 * @param array $tables Tables to use
	 * @param array $joins Joins to use
	 * @param EntityId[] $entityIds EntityIds to select
	 * @param IDatabase $db DB to query on
	 * @return array of rows with keys of their entity ID serializations
	 */
	public function selectRows(
		array $fields,
		array $tables,
		array $joins,
		array $entityIds,
		IDatabase $db
	) {
		// Needed for mapRowsToEntityIds
		$fields[] = 'page_title';

		list( $where, $slotJoinConds, $extraFields ) = $this->getQueryInfo( $entityIds, $db );
		$joins = array_merge( $joins, $slotJoinConds );

		$res = $db->select(
			array_merge( $tables, array_keys( $slotJoinConds ) ),
			array_merge( $fields, $extraFields ),
			$where,
			__METHOD__,
			[],
			$joins
		);

		return $this->mapRowsToEntityIds( $res );
	}

	/**
	 * @param EntityId $entityIds
	 * @param IDatabase $db
	 * @return array [ string $whereCondition, array $extraTables ]
	 */
	private function getQueryInfo( array $entityIds, IDatabase $db ) {
		$where = [];
		$slotJoinConds = [];

		foreach ( $entityIds as $entityId ) {
			$entityType = $entityId->getEntityType();
			$slotRole = $this->entityNamespaceLookup->getEntitySlotRole( $entityType );
			$namespace = $this->entityNamespaceLookup->getEntityNamespace( $entityType );

			$conditions = [
				'page_title' => $entityId->getLocalPart(),
				'page_namespace' => $namespace,
			];

			/**
			 * Only check against the slot role when we are not using the main slot.
			 * If we are using the main slot, then we only need to check that the page
			 * exists rather than a specific slot within the page.
			 * This ensures comparability with the pre MCR schema as long as only the
			 * main slot is used.
			 */
			if ( $slotRole !== 'main' ) {
				try {
					$slotRoleId = $this->slotRoleStore->getId( $slotRole );
				} catch ( NameTableAccessException $e ) {
					// The slot role is not yet saved, nothing to retrieve.
					continue;
				}

				$conditions['slot_role_id'] = $slotRoleId;
				$slotJoinConds = [ 'slots' => [ 'INNER JOIN', 'page_latest=slot_revision_id' ] ];
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

		return [ $db->makeList( $where, LIST_OR ), $slotJoinConds, [ 'page_title' ] ];
	}

	/**
	 * @param Traversable $rows
	 * @return array of rows with keys of their entity ID serializations
	 */
	private function mapRowsToEntityIds( Traversable $rows ) {
		$indexedRows = [];

		foreach ( $rows as $row ) {
			$indexedRows[$row->page_title] = $row;
		}

		return $indexedRows;
	}

}

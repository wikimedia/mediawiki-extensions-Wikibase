<?php

namespace Wikibase\Client\Usage\Sql;

use DatabaseBase;
use InvalidArgumentException;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Helper class for updating the wb_entity_usage table.
 * This is used internally by SqlUsageTracker.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class UsageTableUpdater {

	/**
	 * @var DatabaseBase
	 */
	private $connection;

	/**
	 * @var string
	 */
	private $tableName;

	/**
	 * @var int
	 */
	private $batchSize;

	/**
	 * @param DatabaseBase $connection
	 * @param string $tableName
	 * @param int $batchSize
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( DatabaseBase $connection, $tableName, $batchSize ) {
		if ( !is_string( $tableName ) ) {
			throw new InvalidArgumentException( '$tableName must be a string' );
		}

		if ( !is_int( $batchSize ) || $batchSize <= 0 ) {
			throw new InvalidArgumentException( '$batchSize must be a positive integer' );
		}

		$this->connection = $connection;
		$this->tableName = $tableName;
		$this->batchSize = $batchSize;
	}

	/**
	 * Re-indexes the given list of EntityUsagess so that each EntityUsage can be found by using its
	 * string representation as a key.
	 *
	 * @param EntityUsage[] $usages
	 *
	 * @throws InvalidArgumentException
	 * @return EntityUsage[]
	 */
	private function reindexEntityUsages( array $usages ) {
		$reindexed = array();

		foreach ( $usages as $usage ) {
			if ( !( $usage instanceof EntityUsage ) ) {
				throw new InvalidArgumentException( '$usages must contain EntityUsage objects.' );
			}

			$key = $usage->getIdentifier();
			$reindexed[$key] = $usage;
		}

		return $reindexed;
	}

	/**
	 * @param int $pageId
	 * @param EntityUsage[] $oldUsages
	 * @param EntityUsage[] $newUsages
	 *
	 * @return int The number of usages added or removed
	 */
	public function updateUsage( $pageId, array $oldUsages, array $newUsages ) {
		$newUsages = $this->reindexEntityUsages( $newUsages );
		$oldUsages = $this->reindexEntityUsages( $oldUsages );

		$removed = array_diff_key( $oldUsages, $newUsages );
		$added = array_diff_key( $newUsages, $oldUsages );

		$changes = 0;
		$changes += $this->removeUsageForPage( $pageId, $removed );
		$changes += $this->addUsageForPage( $pageId, $added );

		return $changes;
	}

	/**
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 *
	 * @return int The number of entries removed
	 */
	private function removeUsageForPage( $pageId, array $usages ) {
		if ( empty( $usages ) ) {
			return 0;
		}

		$bins = $this->binUsages( $usages );
		$count = 0;

		foreach ( $bins as $aspect => $bin ) {
			$count += $this->removeAspectForPage( $pageId, $aspect, array_keys( $bin ) );
		}

		return $count;
	}

	/**
	 * Collects the entity id strings contained in the given list of EntityUsages into
	 * bins based on the usage's aspect.
	 *
	 * @param EntityUsage[] $usages
	 *
	 * @throws InvalidArgumentException
	 * @return array[] two dimensional associative array mapping aspect ids and entity id strings.
	 */
	private function binUsages( array $usages ) {
		$bins = array();

		foreach ( $usages as $usage ) {
			if ( !( $usage instanceof EntityUsage ) ) {
				throw new InvalidArgumentException( '$usages must contain EntityUsage objects.' );
			}

			$aspect = $usage->getAspect();
			$idString = $usage->getEntityId()->getSerialization();

			$bins[$aspect][$idString] = null;
		}

		return $bins;
	}

	/**
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 *
	 * @throws InvalidArgumentException
	 * @return array[] A list of rows for use with DatabaseBase::insert
	 */
	private function makeUsageRows( $pageId, array $usages ) {
		$rows = array();

		foreach ( $usages as $usage ) {
			if ( !( $usage instanceof EntityUsage ) ) {
				throw new InvalidArgumentException( '$usages must contain EntityUsage objects.' );
			}

			$rows[] = array(
				'eu_page_id' => (int)$pageId,
				'eu_aspect' => (string)$usage->getAspect(),
				'eu_entity_id' => (string)$usage->getEntityId()->getSerialization(),
				'eu_entity_type' => (string)$usage->getEntityId()->getEntityType(),
			);
		}

		return $rows;
	}

	/**
	 * @param int $pageId
	 * @param string $aspect
	 * @param string[] $idStrings
	 *
	 * @return int The number of entries removed
	 */
	private function removeAspectForPage( $pageId, $aspect, array $idStrings ) {
		if ( empty( $idStrings ) ) {
			return 0;
		}

		$batches = array_chunk( $idStrings, $this->batchSize, true );
		$count = 0;

		foreach ( $batches as $batch ) {
			$this->connection->delete(
				$this->tableName,
				array(
					'eu_page_id' => (int)$pageId,
					'eu_aspect' => (string)$aspect,
					'eu_entity_id' => $batch,
				),
				__METHOD__
			);

			$count += $this->connection->affectedRows();
		}

		return $count;
	}

	/**
	 * @param int $pageId
	 * @param EntityUsage[] $usages Must be keyed by string id
	 *
	 * @return int The number of entries added
	 */
	private function addUsageForPage( $pageId, array $usages ) {
		if ( empty( $usages ) ) {
			return 0;
		}

		$batches = array_chunk(
			$this->makeUsageRows( $pageId, $usages ),
			$this->batchSize
		);

		$count = 0;

		foreach ( $batches as $rows ) {
			$this->connection->insert(
				$this->tableName,
				$rows,
				__METHOD__
			);

			$count += $this->connection->affectedRows();
		}

		return $count;
	}

	/**
	 * Re-indexes the given list of EntityIds so that each EntityId can be found by using its
	 * string representation as a key.
	 *
	 * @param EntityId[] $entityIds
	 *
	 * @throws InvalidArgumentException
	 * @return EntityId[]
	 */
	private function reindexEntityIds( array $entityIds ) {
		$reindexed = array();

		foreach ( $entityIds as $entityId ) {
			if ( !( $entityId instanceof EntityId ) ) {
				throw new InvalidArgumentException( '$entityIds must contain EntityId objects.' );
			}

			$key = $entityId->getSerialization();
			$reindexed[$key] = $entityId;
		}

		return $reindexed;
	}

	/**
	 * Removes usage tracking for the given set of entities.
	 * This is used typically when entities were deleted.
	 *
	 * @param EntityId[] $entityIds
	 */
	public function removeEntities( array $entityIds ) {
		if ( empty( $entityIds ) ) {
			return;
		}

		$entityIds = $this->reindexEntityIds( $entityIds );
		$batches = array_chunk( array_keys( $entityIds ), $this->batchSize, true );

		foreach ( $batches as $batch ) {
			$this->connection->delete(
				$this->tableName,
				array(
					'eu_entity_id' => $batch,
				),
				__METHOD__
			);
		}
	}

}

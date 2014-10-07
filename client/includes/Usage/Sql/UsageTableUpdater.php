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
	 * @throws \InvalidArgumentException
	 */
	public function __construct( DatabaseBase $connection, $tableName, $batchSize ) {
		if ( !is_string( $tableName ) ) {
			throw new InvalidArgumentException( '$tableName must be a string' );
		}

		if ( !is_int( $batchSize ) || $batchSize < 1 ) {
			throw new InvalidArgumentException( '$batchSize must be an integer >= 1' );
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

			$key = $usage->getIdentityString();
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

		$mod = 0;
		$mod += $this->removeUsageForPage( $pageId, $removed );
		$mod += $this->addUsageForPage( $pageId, $added );

		return $mod;
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
		$c = 0;

		foreach ( $bins as $aspect => $entities ) {
			$c += $this->removeAspectForPage( $pageId, $aspect, array_keys( $entities ) );
		}

		return $c;
	}

	/**
	 * Collects the EntityIds contained in the given list of EntityUsages into
	 * bins based on the usage's aspect.
	 *
	 * @param EntityUsage[] $usages
	 *
	 * @throws InvalidArgumentException
	 * @return array[] an associative array mapping aspect ids to lists of EntityIds.
	 */
	private function binUsages( array $usages ) {
		$bins = array();

		foreach ( $usages as $usage ) {
			if ( !( $usage instanceof EntityUsage ) ) {
				throw new InvalidArgumentException( '$usages must contain EntityUsage objects.' );
			}

			$aspect = $usage->getAspect();
			$id = $usage->getEntityId();
			$key = $id->getSerialization();

			$bins[$aspect][$key] = $id;
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
	 * @param string[] $entityIdStrings String IDs of the entities to be removed.
	 *
	 * @return int The number of entries removed
	 */
	private function removeAspectForPage( $pageId, $aspect, array $entityIdStrings ) {
		if ( empty( $entityIdStrings ) ) {
			return 0;
		}

		$batches = array_chunk( $entityIdStrings, $this->batchSize, true );
		$c = 0;

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

			$c += $this->connection->affectedRows();
		}

		return $c;
	}

	/**
	 * @param int $pageId
	 * @param EntityUsage[] $usages
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

		$c = 0;

		foreach ( $batches as $rows ) {
			$this->connection->insert(
				$this->tableName,
				$rows,
				__METHOD__
			);

			$c += $this->connection->affectedRows();
		}

		return $c;
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

		foreach ( $entityIds as $id ) {
			if ( !( $id instanceof EntityId ) ) {
				throw new InvalidArgumentException( '$entityIds must contain EntityId objects.' );
			}

			$key = $id->getSerialization();
			$reindexed[$key] = $id;
		}

		return $reindexed;
	}

	/**
	 * Removes usage tracking for the given set of entities.
	 * This is used typically when entities were deleted.
	 *
	 * @param EntityId[] $entities
	 */
	public function removeEntities( array $entities ) {
		if ( empty( $entities ) ) {
			return;
		}

		$entities = $this->reindexEntityIds( $entities );
		$batches = array_chunk( $entities, $this->batchSize, true );

		foreach ( $batches as $batch ) {
			$this->connection->delete(
				$this->tableName,
				array(
					'eu_entity_id' => array_keys( $batch ),
				),
				__METHOD__
			);
		}
	}

}

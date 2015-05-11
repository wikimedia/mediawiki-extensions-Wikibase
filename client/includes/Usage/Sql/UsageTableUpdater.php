<?php

namespace Wikibase\Client\Usage\Sql;

use DatabaseBase;
use InvalidArgumentException;
use Wikibase\Client\Usage\EntityUsage;

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

		if ( !is_int( $batchSize ) || $batchSize < 1 ) {
			throw new InvalidArgumentException( '$batchSize must be an integer >= 1' );
		}

		$this->connection = $connection;
		$this->tableName = $tableName;
		$this->batchSize = $batchSize;
	}

	/**
	 * Re-indexes the given list of EntityUsages so that each EntityUsage can be found by using its
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
	 * Updates the recorded usage. Old usages are not removed. Usages that are in both
	 * $oldUsages and $newUsages have their touched date updated to $touched.
	 *
	 * @param int $pageId
	 * @param EntityUsage[] $oldUsages Existing usage entries in the database.
	 * @param EntityUsage[] $newUsages Desired usage entries whish should be in the database.
	 * @param string|false $touched timestamp, or false in case $newUsages is empty.
	 *
	 * @return int The number of usages added or removed
	 */
	public function updateUsage( $pageId, array $oldUsages, array $newUsages, $touched ) {
		if ( !empty( $newUsages ) && $touched === false ) {
			throw new InvalidArgumentException( '$touched is false, but $newUsages is not empty.' );
		}

		$newUsages = $this->reindexEntityUsages( $newUsages );
		$oldUsages = $this->reindexEntityUsages( $oldUsages );

		$keep = array_intersect_key( $oldUsages, $newUsages );
		$added = array_diff_key( $newUsages, $oldUsages );

		// update the "touched" timestamp for the remaining entries
		$this->touchUsages( $pageId, $keep, $touched );

		return $this->addUsageForPage( $pageId, $added, $touched );
	}

	/**
	 * Sets the "touched" timestamp for the given usages.
	 *
	 * @param int $pageId
	 * @param array|EntityUsage $usages
	 * @param string|false $touched timestamp
	 */
	private function touchUsages( $pageId, array $usages, $touched ) {
		if ( $touched === false || empty( $usages ) ) {
			return;
		}

		$db = $this->connection;

		$usageConditions = array_map( function( EntityUsage $usage ) use ( $db ) {
			return $db->makeList( array(
				'eu_aspect' => $usage->getAspectKey(),
				'eu_entity_id' => $usage->getEntityId()->getSerialization(),
			), LIST_AND );
		}, $usages );

		// XXX: Do we need batching here? List pages may be using hundreds of entities...
		$this->connection->update(
			$this->tableName,
			array(
				'eu_touched' => wfTimestamp( TS_MW, $touched ),
			),
			array(
				'eu_page_id' => (int)$pageId,
				$this->connection->makeList( $usageConditions, LIST_OR )
			),
			__METHOD__
		);
	}

	/**
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 * @param string $touched timestamp
	 *
	 * @throws InvalidArgumentException
	 * @return array[]
	 */
	private function makeUsageRows( $pageId, array $usages, $touched ) {
		$rows = array();

		foreach ( $usages as $usage ) {
			if ( !( $usage instanceof EntityUsage ) ) {
				throw new InvalidArgumentException( '$usages must contain EntityUsage objects.' );
			}

			$rows[] = array(
				'eu_page_id' => (int)$pageId,
				'eu_aspect' => $usage->getAspectKey(),
				'eu_entity_id' => $usage->getEntityId()->getSerialization(),
				'eu_touched' => wfTimestamp( TS_MW, $touched ),
			);
		}

		return $rows;
	}

	/**
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 * @param string|false $touched timestamp, may be false only if $usages is empty.
	 *
	 * @return int The number of entries added
	 */
	private function addUsageForPage( $pageId, array $usages, $touched ) {
		if ( empty( $usages ) ) {
			return 0;
		}

		if ( !is_string( $touched ) || $touched === '' ) {
			throw new InvalidArgumentException( '$touched is not a timestamp, but $usages is not empty.' );
		}

		$batches = array_chunk(
			$this->makeUsageRows( $pageId, $usages, $touched ),
			$this->batchSize
		);

		$c = 0;

		foreach ( $batches as $rows ) {
			$this->connection->insert( $this->tableName, $rows, __METHOD__, array( 'IGNORE' ) );
			$c += $this->connection->affectedRows();
		}

		return $c;
	}

	/**
	 * Removes usage tracking entries that were last updated before the given
	 * timestamp.
	 *
	 * @see UsageTracker::pruneStaleUsages
	 *
	 * @param int $pageId
	 * @param string $lastUpdatedBefore timestamp
	 */
	public function pruneStaleUsages( $pageId, $lastUpdatedBefore ) {
		//FIXME: TEST ME!

		if ( empty( $lastUpdatedBefore ) ) {
			return;
		}

		$lastUpdatedBefore = wfTimestamp( TS_MW, $lastUpdatedBefore );

		// XXX: we may want to batch this
		$this->connection->delete(
			$this->tableName,
			array(
				'eu_page_id' => (int)$pageId,
				'eu_touched < ' . $this->connection->addQuotes( $lastUpdatedBefore ),
			),
			__METHOD__
		);
	}

	/**
	 * Removes usage tracking for the given set of entities.
	 * This is used typically when entities were deleted.
	 *
	 * @see UsageTracker::removeEntities
	 *
	 * @param string[] $idStrings
	 */
	public function removeEntities( array $idStrings ) {
		if ( empty( $idStrings ) ) {
			return;
		}

		$batches = array_chunk( $idStrings, $this->batchSize );

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

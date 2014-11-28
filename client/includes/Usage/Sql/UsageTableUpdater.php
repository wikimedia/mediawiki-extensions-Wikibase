<?php

namespace Wikibase\Client\Usage\Sql;

use DatabaseBase;
use InvalidArgumentException;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\UsageTracker;

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
	 * @var int
	 */
	private $batchSize;

	/**
	 * @param DatabaseBase $connection
	 * @param int $batchSize
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( DatabaseBase $connection, $batchSize ) {
		if ( !is_int( $batchSize ) || $batchSize < 1 ) {
			throw new InvalidArgumentException( '$batchSize must be an integer >= 1' );
		}

		$this->connection = $connection;
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
	 * Updates the recorded usage, removing all obsolete usages.
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

		$removed = array_diff_key( $oldUsages, $newUsages );
		$added = array_diff_key( $newUsages, $oldUsages );

		$mod = 0;
		$mod += $this->removeUsageForPage( $pageId, $removed );

		// update the "touched" timestamp for the remaining entries
		$this->touchUsageForPage( $pageId, $touched );

		$mod += $this->addUsageForPage( $pageId, $added, $touched );

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

		foreach ( $bins as $aspect => $bin ) {
			$c += $this->removeAspectForPage( $pageId, $aspect, $bin );
		}

		return $c;
	}

	/**
	 * Sets the "touched" timestamp for all usage entries for the given page to $touched.
	 * If $touched is false, this does nothing. This is intended to be used in cases where
	 * it is already known that no usage records remain in the database.
	 *
	 * @param int $pageId
	 * @param string|false $touched timestamp
	 */
	private function touchUsageForPage( $pageId, $touched ) {
		if ( $touched === false ) {
			return;
		}

		$this->connection->update(
			UsageTracker::TABLE_NAME,
			array(
				'eu_touched' => wfTimestamp( TS_MW, $touched ),
			),
			array(
				'eu_page_id' => (int)$pageId,
			),
			__METHOD__
		);
	}

	/**
	 * Collects the entity id strings contained in the given list of EntityUsages into
	 * bins based on the usage's aspect and modifier.
	 *
	 * @param EntityUsage[] $usages
	 *
	 * @throws InvalidArgumentException
	 * @return array[] an associative array mapping aspect ids to lists of entity id strings.
	 */
	private function binUsages( array $usages ) {
		$bins = array();

		foreach ( $usages as $usage ) {
			if ( !( $usage instanceof EntityUsage ) ) {
				throw new InvalidArgumentException( '$usages must contain EntityUsage objects.' );
			}

			$aspect = $usage->getAspectKey();
			$bins[$aspect][] = $usage->getEntityId()->getSerialization();
		}

		return $bins;
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
	 * @param string $aspect
	 * @param string[] $idStrings Id strings of the entities to be removed.
	 *
	 * @return int The number of entries removed
	 */
	private function removeAspectForPage( $pageId, $aspect, array $idStrings ) {
		if ( empty( $idStrings ) ) {
			return 0;
		}

		$batches = array_chunk( $idStrings, $this->batchSize );
		$c = 0;

		foreach ( $batches as $batch ) {
			$this->connection->delete(
				UsageTracker::TABLE_NAME,
				array(
					'eu_page_id' => (int)$pageId,
					'eu_aspect' => $aspect,
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
	 * @param string|false $touched timestamp, may be false only if $usages is empty.
	 *
	 * @throws InvalidArgumentException
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
			$this->connection->insert( UsageTracker::TABLE_NAME, $rows, __METHOD__, array( 'IGNORE' ) );

			$c += $this->connection->affectedRows();
		}

		return $c;
	}

	/**
	 * Removes usage tracking for the given set of entities.
	 * This is used typically when entities were deleted.
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
				UsageTracker::TABLE_NAME,
				array(
					'eu_entity_id' => $batch,
				),
				__METHOD__
			);
		}
	}

}

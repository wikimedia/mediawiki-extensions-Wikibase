<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Store\Sql;

use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikimedia\Rdbms\DBConnRef;

/**
 * Utility class for collecting dispatch statistics.
 *
 * @license GPL-2.0-or-later
 */
class DispatchStats {

	/**
	 * @var RepoDomainDb
	 */
	private $db;

	public function __construct( RepoDomainDb $repoDomainDb ) {
		$this->db = $repoDomainDb;
	}

	public function getDispatchStats(): array {
		$db = $this->db->connections()->getReadConnection();
		$limit = 5000;

		$limitedNumberOfChanges = $this->loadLimitedNumberOfChanges( $db, $limit );
		if ( $limitedNumberOfChanges === 0 ) {
			return [ 'numberOfChanges' => 0 ];
		}
		$changeTimesStats = $this->loadChangeTimes( $db );
		if ( $limitedNumberOfChanges > $limit ) {
			$estimate = $this->getWbChangesRowEstimate( $db );
			if ( $estimate < $limit ) {
				// estimate is outdated
				return $this->buildMinimumNumberOfChangesStats( $limitedNumberOfChanges, $changeTimesStats );
			}
			return $this->buildEstimateStats( $estimate, $changeTimesStats );
		}

		$numberOfEntities = $this->loadNumberOfEntities( $db );

		return $this->buildExactNumberOfChangesStats( $limitedNumberOfChanges, $numberOfEntities, $changeTimesStats );
	}

	private function loadLimitedNumberOfChanges( DBConnRef $db, $limit ): int {
		return $db->newSelectQueryBuilder()
			->select( '*' )
			->from( 'wb_changes' )
			->limit( $limit + 1 )
			->caller( __METHOD__ )
			->fetchRowCount();
	}

	private function getWbChangesRowEstimate( DBConnRef $db ): int {
		return $db->newSelectQueryBuilder()
			->select( '*' )
			->from( 'wb_changes' )
			->caller( __METHOD__ )
			->estimateRowCount();
	}

	private function loadNumberOfEntities( DBConnRef $db ): int {
		return (int)$db->newSelectQueryBuilder()
			->select( 'COUNT( DISTINCT change_object_id )' )
			->from( 'wb_changes' )
			->caller( __METHOD__ )
			->fetchField();
	}

	private function loadChangeTimes( DBConnRef $db ): array {
		$statsRow = $db->newSelectQueryBuilder()
			->select( [
				'stalestTime' => 'MIN( change_time )',
				'freshestTime' => 'MAX( change_time )',
			] )
			->from( 'wb_changes' )
			->caller( __METHOD__ )
			->fetchRow();

		return get_object_vars( $statsRow );
	}

	private function buildMinimumNumberOfChangesStats( int $limitedNumberOfChanges, array $changeTimesStats ): array {
		return [
				'minimumNumberOfChanges' => $limitedNumberOfChanges,
			] + $changeTimesStats;
	}

	private function buildEstimateStats( $estimate, array $changeTimesStats ): array {
		return [
				'estimatedNumberOfChanges' => $estimate,
			] + $changeTimesStats;
	}

	private function buildExactNumberOfChangesStats( int $numberOfChanges, int $numberOfEntities, array $changeTimesStats ): array {
		return [
				'numberOfChanges' => $numberOfChanges,
				'numberOfEntities' => $numberOfEntities,
			] + $changeTimesStats;
	}

}

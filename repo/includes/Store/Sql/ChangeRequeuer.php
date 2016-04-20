<?php

namespace Wikibase\Repo\Store\SQL;

use DBReplicationWaitError;
use LoadBalancer;
use MWException;

/**
 * @author Addshore
 * @license GPL-2.0+
 */
class ChangeRequeuer {

	/**
	 * @var LoadBalancer
	 */
	private $loadBalancer;

	public function __construct( LoadBalancer $loadBalancer ) {
		$this->loadBalancer = $loadBalancer;
	}

	/**
	 * @param string $from timestamp accepted by wfTimestamp
	 * @param string $to timestamp accepted by wfTimestamp
	 *
	 * @return int[]
	 * @throws MWException
	 */
	public function getChangeRowIdsInRange( $from, $to ) {
		$dbr = $this->loadBalancer->getConnection( DB_SLAVE );

		$ids = $dbr->selectFieldValues(
			'wb_changes',
			'change_id',
			array(
				'change_time >= ' . $dbr->addQuotes( $dbr->timestamp( $from ) ),
				'change_time <= ' . $dbr->addQuotes( $dbr->timestamp( $to ) ),
			),
			__METHOD__
		);

		$this->loadBalancer->reuseConnection( $dbr );

		if ( !$ids ) {
			throw new MWException( 'Failed to get change row ids.' );
		}

		return array_map( 'intval' , $ids );
	}

	/**
	 * @param int[] $changeIds
	 * @param int $batchSize
	 *
	 * @throws DBReplicationWaitError
	 */
	public function requeueRowBatch( array $changeIds, $batchSize ) {
		$dbw = $this->loadBalancer->getConnection( DB_MASTER );

		foreach ( array_chunk( $changeIds, $batchSize ) as $changeIdBatch ) {
			$dbw->insertSelect(
				'wb_changes',
				'wb_changes',
				array(
					'change_type' => 'change_type',
					'change_time' => 'change_time',
					'change_object_id' => 'change_object_id',
					'change_revision_id' => 'change_revision_id',
					'change_user_id' => 'change_user_id',
					'change_info' => 'change_info',
				),
				array(
					'change_id' => $changeIdBatch
				)
			);
			\wfGetLBFactory()->waitForReplication();
		}

		$this->loadBalancer->reuseConnection( $dbw );
	}

}

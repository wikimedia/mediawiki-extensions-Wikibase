<?php

namespace Wikibase\Repo\Store\Sql;

use RuntimeException;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Repo\Store\IdGenerator;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * Unique Id generator implemented using an SQL table.
 * The table needs to have the fields id_value and id_type.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SqlIdGenerator implements IdGenerator {

	/** @var RepoDomainDb */
	private $db;

	/**
	 * @var int[][]
	 */
	private $reservedIds;

	/**
	 * @var bool whether use a separate master database connection to generate new id or not.
	 */
	private $separateDbConnection;

	/**
	 * @param RepoDomainDb $db
	 * @param int[][] $reservedIds
	 * @param bool $separateDbConnection
	 */
	public function __construct(
		RepoDomainDb $db,
		array $reservedIds = [],
		$separateDbConnection = false
	) {
		$this->db = $db;
		$this->reservedIds = $reservedIds;
		$this->separateDbConnection = $separateDbConnection;
	}

	/**
	 * @see IdGenerator::getNewId
	 *
	 * @param string $type normally is content model id (e.g. wikibase-item or wikibase-property)
	 *
	 * @throws RuntimeException if getting an unique ID failed
	 * @return int
	 */
	public function getNewId( $type ) {
		$flags = ( $this->separateDbConnection === true ) ? ILoadBalancer::CONN_TRX_AUTOCOMMIT : 0;
		$database = $this->db->connections()->getWriteConnection( $flags );
		$id = $this->generateNewId( $database, $type );

		return $id;
	}

	/**
	 * Generates and returns a new ID.
	 *
	 * @param IDatabase $database
	 * @param string $type
	 * @param bool $retry Retry once in case of e.g. race conditions. Defaults to true.
	 *
	 * @throws RuntimeException
	 * @return int
	 */
	private function generateNewId( IDatabase $database, $type, $retry = true ) {
		$database->startAtomic( __METHOD__ );

		$currentId = $database->newSelectQueryBuilder()
			->select( 'id_value' )
			->from( 'wb_id_counters' )
			->where( [ 'id_type' => $type ] )
			->forUpdate()
			->caller( __METHOD__ )->fetchRow();

		if ( is_object( $currentId ) ) {
			$id = $currentId->id_value + 1;
			$success = $database->update(
				'wb_id_counters',
				[ 'id_value' => $id ],
				[ 'id_type' => $type ],
				__METHOD__
			);
		} else {
			$id = 1;

			$success = $database->insert(
				'wb_id_counters',
				[
					'id_value' => $id,
					'id_type' => $type,
				],
				__METHOD__
			);

			// Retry once, since a race condition on initial insert can cause one to fail.
			// Race condition is possible due to occurrence of phantom reads is possible
			// at non serializable transaction isolation level.
			if ( !$success && $retry ) {
				$id = $this->generateNewId( $database, $type, false );
				$success = true;
			}
		}

		$database->endAtomic( __METHOD__ );

		if ( !$success ) {
			throw new RuntimeException( 'Could not generate a reliably unique ID.' );
		}

		if ( array_key_exists( $type, $this->reservedIds ) && in_array( $id, $this->reservedIds[$type] ) ) {
			$id = $this->generateNewId( $database, $type );
		}

		return $id;
	}

}

<?php

namespace Wikibase;

use MWException;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\LoadBalancer;

/**
 * Unique Id generator implemented using an SQL table.
 * The table needs to have the fields id_value and id_type.
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SqlIdGenerator implements IdGenerator {

	/**
	 * @var LoadBalancer
	 */
	private $loadBalancer;

	/**
	 * @var int[]
	 */
	private $idBlacklist;

	/**
	 * @param LoadBalancer $loadBalancer
	 * @param int[] $idBlacklist
	 */
	public function __construct( LoadBalancer $loadBalancer, array $idBlacklist = [] ) {
		$this->loadBalancer = $loadBalancer;
		$this->idBlacklist = $idBlacklist;
	}

	/**
	 * @see IdGenerator::getNewId
	 *
	 * @param string $type normally is content model id (e.g. wikibase-item or wikibase-property)
	 *
	 * @return int
	 */
	public function getNewId( $type ) {
		$database = $this->loadBalancer->getConnection( DB_MASTER );
		$id = $this->generateNewId( $database, $type );
		$this->loadBalancer->reuseConnection( $database );

		return $id;
	}

	/**
	 * Generates and returns a new ID.
	 *
	 * @param IDatabase $database
	 * @param string $type
	 * @param bool $retry Retry once in case of e.g. race conditions. Defaults to true.
	 *
	 * @throws MWException
	 * @return int
	 */
	private function generateNewId( IDatabase $database, $type, $retry = true ) {
		$database->startAtomic( __METHOD__ );

		$currentId = $database->selectRow(
			'wb_id_counters',
			'id_value',
			[ 'id_type' => $type ],
			__METHOD__,
			[ 'FOR UPDATE' ]
		);

		if ( is_object( $currentId ) ) {
			$id = $currentId->id_value + 1;
			$success = $database->update(
				'wb_id_counters',
				[ 'id_value' => $id ],
				[ 'id_type' => $type ]
			);
		} else {
			$id = 1;

			$success = $database->insert(
				'wb_id_counters',
				[
					'id_value' => $id,
					'id_type' => $type,
				]
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
			throw new MWException( 'Could not generate a reliably unique ID.' );
		}

		if ( in_array( $id, $this->idBlacklist ) ) {
			$id = $this->generateNewId( $database, $type );
		}

		return $id;
	}

}

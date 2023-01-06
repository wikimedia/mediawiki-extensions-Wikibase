<?php

namespace Wikibase\Repo\Store\Sql;

use RuntimeException;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Repo\Store\IdGenerator;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * Unique Id generator implemented using an SQL table and an UPSERT query.
 * The table needs to have the fields id_value and id_type.
 *
 * The UPSERT approach was created in https://phabricator.wikimedia.org/T194299
 * as wikidata.org was having issues with the old SqlIdGenerator.
 *
 * LAST_INSERT_ID from mysql is used in this class, which means that this IdGenerator
 * can only be used with MySQL.
 * This class depends on the upsert implementation within the RDBMS library for
 * different DB backends.
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class UpsertSqlIdGenerator implements IdGenerator {

	/** @var RepoDomainDb */
	private $db;

	/**
	 * @var int[][]
	 */
	private $reservedIds;

	/**
	 * Limit for id generation attempts that hit reserved ids.
	 * We have not had any reserved ids in the past with anywhere near this number of sequential entity ids.
	 * @var int
	 */
	private const MAX_ATTEMPTS = 10;

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
	 * @return int
	 *
	 * @throws RuntimeException
	 */
	public function getNewId( $type ) {
		$flags = ( $this->separateDbConnection === true ) ? ILoadBalancer::CONN_TRX_AUTOCOMMIT : 0;
		$database = $this->db->connections()->getWriteConnection( $flags );

		$idGenerations = 0;
		do {
			if ( $idGenerations >= self::MAX_ATTEMPTS ) {
				throw new RuntimeException(
					"Could not generate a non-reserved ID of type '$type', tried " . self::MAX_ATTEMPTS . ' times.'
				);
			}
			$id = $this->generateNewId( $database, $type );
			$idGenerations++;

		} while ( $this->idIsReserved( $type, $id ) );

		return $id;
	}

	private function idIsReserved( $type, $id ) {
		return array_key_exists( $type, $this->reservedIds ) && in_array( $id, $this->reservedIds[$type] );
	}

	/**
	 * Generates and returns a new ID.
	 *
	 * @param IDatabase $database
	 * @param string $type
	 *
	 * @throws RuntimeException
	 * @return int
	 */
	private function generateNewId( IDatabase $database, $type ) {
		$database->startAtomic( __METHOD__ );

		$success = $this->upsertId( $database, $type );

		// Retry once
		if ( !$success ) {
			$success = $this->upsertId( $database, $type );
		}

		if ( !$success ) {
			throw new RuntimeException( 'Could not generate a reliably unique ID.' );
		}

		$id = $database->insertId();

		$database->endAtomic( __METHOD__ );

		// If the upsert successfully inserts, we won't have an auto increment ID, instead it will be the 1 set in the query.
		if ( !is_int( $id ) || $id === 0 ) {
			$id = 1;
		}

		return $id;
	}

	/**
	 * @param IDatabase $database
	 * @param string $type
	 * @return bool Query success
	 */
	private function upsertId( IDatabase $database, $type ) {
		return $database->upsert(
			'wb_id_counters',
			[
				'id_type' => $type,
				'id_value' => 1,
			],
			'id_type',
			[ 'id_value = LAST_INSERT_ID(id_value + 1)' ],
			__METHOD__
		);
	}

}

<?php

namespace Wikibase;

use DatabaseBase;
use MWException;
use Wikibase\Repo\WikibaseRepo;

/**
 * Unique Id generator implemented using an SQL table.
 * The table needs to have the fields id_value and id_type.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SqlIdGenerator implements IdGenerator {

	/**
	 * @var string
	 */
	private $table;

	/**
	 * @var DatabaseBase
	 */
	private $db;

	/**
	 * @param string $tableName
	 * @param DatabaseBase $database
	 */
	public function __construct( $tableName, DatabaseBase $database ) {
		$this->table = $tableName;
		$this->db = $database;
	}

	/**
	 * @see IdGenerator::getNewId
	 *
	 * @param string $type
	 *
	 * @return int
	 */
	public function getNewId( $type ) {
		return $this->generateNewId( $type );
	}

	/**
	 * Generates and returns a new ID.
	 *
	 * @since 0,1
	 *
	 * @param string $type
	 * @param bool $retry Retry once in case of e.g. race conditions. Defaults to true.
	 *
	 * @throws MWException
	 * @return int
	 */
	protected function generateNewId( $type, $retry = true ) {
		$trx = $this->db->trxLevel();

		if ( $trx == 0 ) {
			$this->db->begin( __METHOD__ );
		}

		$currentId = $this->db->selectRow(
			$this->table,
			'id_value',
			array( 'id_type' => $type )
		);

		if ( is_object( $currentId ) ) {
			$id = $currentId->id_value + 1;

			$success = $this->db->update(
				$this->table,
				array( 'id_value' => $id ),
				array( 'id_type' => $type )
			);
		}
		else {
			$id = 1;

			$success = $this->db->insert(
				$this->table,
				array(
					'id_value' => $id,
					'id_type' => $type,
				)
			);

			// Retry once, since a race condition on initial insert can cause one to fail.
			// Race condition is possible due to occurrence of phantom reads is possible
			// at non serializable transaction isolation level.
			if ( !$success && $retry ) {
				$id = $this->getNewId( $type, false );
				$success = true;
			}
		}

		if ( $trx == 0 ) {
			$this->db->commit( __METHOD__ );
		}

		if ( !$success ) {
			throw new MWException( 'Could not generate a reliably unique ID.' );
		}

		$idBlacklist = WikibaseRepo::getDefaultInstance()->
			getSettings()->getSetting( 'idBlacklist' );

		if ( in_array( $id, $idBlacklist ) ) {
			$id = $this->generateNewId( $type );
		}

		return $id;
	}

}

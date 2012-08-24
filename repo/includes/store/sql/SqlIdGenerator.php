<?php

namespace Wikibase;

/**
 * Unique Id generator implemented using an SQL table.
 * The table needs to have the fields id_value and id_type.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SqlIdGenerator implements IdGenerator {

	/**
	 * @since 0.1
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * @since 0.1
	 *
	 * @var \DatabaseBase
	 */
	protected $db;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param string $tableName
	 * @param \DatabaseBase $database
	 */
	public function __construct( $tableName, \DatabaseBase $database ) {
		$this->table = $tableName;
		$this->db = $database;
	}

	/**
	 * @see IdIncrementer::getNewId
	 *
	 * @since 0.1
	 *
	 * @param string $type
	 *
	 * @return integer
	 */
	public function getNewId( $type ) {
		$this->db->begin( __METHOD__ );

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

			// It's possible (but unlikely) that 1 is returned for multiple concurrent
			// calls if the transaction isolation level is less then serializable.
			$success = $this->db->insert(
				$this->table,
				array(
					'id_value' => $id,
					'id_type' => $type,
				)
			);
		}

		$this->db->commit( __METHOD__ );

		if ( !$success ) {
			throw new \MWException( 'Could not generate a reliably unique ID.' );
		}

		return $id;
	}

}

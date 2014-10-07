<?php

namespace Wikibase;

use IORMRow;
use MWException;

/**
 * Class representing the wb_changes table.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ChangesTable extends \ORMTable implements ChunkAccess {

	/**
	 * Constructor.
	 *
	 * @param String|null $changesDatabase the logical name of the database to interact with.
	 *        If null, Settings::get( 'changesDatabase' ) will be used to determine the target DB.
	 *
	 * @since 0.1
	 */
	public function __construct( $changesDatabase = null ) {
		if ( $changesDatabase === null ) {
			$changesDatabase = Settings::get( 'changesDatabase' );
		}

		$this->setTargetWiki( $changesDatabase );

		$this->fieldPrefix = 'change_';
	}

	/**
	 * @see IORMTable::getName()
	 * @since 0.1
	 * @return string
	 */
	public function getName() {
		return 'wb_changes';
	}

	/**
	 * @see IORMTable::getRowClass()
	 * @since 0.1
	 * @return string
	 */
	public function getRowClass() {
		return 'Wikibase\ChangeRow';
	}

	/**
	 * @see IORMTable::getFields()
	 * @since 0.1
	 * @return array
	 */
	public function getFields() {
		return array(
			'id' => 'id',

			'type' => 'str',
			'time' => 'str', // TS_MW
			'info' => 'data', // handled specially by ChangeRow
			'object_id' => 'str',
			'user_id' => 'int',
			'revision_id' => 'int',
		);
	}

	/**
	 * Returns the name of a class that can handle changes of the provided type.
	 *
	 * @since 0.1
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public static function getClassForType( $type ) {
		$typeMap = Settings::get( 'changeHandlers' );
		return array_key_exists( $type, $typeMap ) ? $typeMap[$type] : 'Wikibase\ChangeRow';
	}

	/**
	 * Factory method to construct a new Wikibase\Change instance.
	 *
	 * @since 0.1
	 *
	 * @param array $data
	 * @param boolean $loadDefaults
	 *
	 * @return Change
	 * @throws MWException
	 */
	public function newRow( array $data, $loadDefaults = false ) {
		if ( !array_key_exists( 'type', $data ) ) {
			throw new MWException( 'The type element must be set in the $data array before a new change can be constructed.' );
		}

		$class = static::getClassForType( $data['type'] );

		return new $class( $this, $data, $loadDefaults );
	}

	/**
	 * @see ORMTable::getWriteValues()
	 *
	 * @since 0.4
	 *
	 * @param ChangeRow|IORMRow $row
	 *
	 * @throws MWException
	 * @return array
	 */
	protected function getWriteValues( IORMRow $row ) {
		$values = parent::getWriteValues( $row );

		$infoField = $this->getPrefixedField( 'info' );
		$revisionIdField = $this->getPrefixedField( 'revision_id' );
		$userIdField = $this->getPrefixedField( 'user_id' );

		if ( isset( $values[$infoField] ) ) {
			if ( !( $row instanceof ChangeRow ) ) {
				throw new MWException( '$row must be a ChangeRow.' );
			}

			$values[$infoField] = $row->serializeInfo( $values[$infoField] );
		}

		if ( !isset( $values[$revisionIdField] ) ) {
			$values[$revisionIdField] = 0;
		}

		if ( !isset( $values[$userIdField] ) ) {
			$values[$userIdField] = 0;
		}

		return $values;
	}

	/**
	 * Returns a chunk of Change records, starting at the given change ID.
	 *
	 * @param int $start The change ID to start at
	 * @param int $size  The desired number of Change objects
	 *
	 * @return Change[]
	 */
	public function loadChunk( $start, $size ) {
		wfProfileIn( __METHOD__ );

		$changes = $this->selectObjects(
			null,
			array(
				'id >= ' . intval( $start )
			),
			array(
				'LIMIT' => $size,
				'ORDER BY ' => $this->getPrefixedField( 'id' ) . ' ASC'
			),
			__METHOD__
		);

		wfProfileOut( __METHOD__ );
		return $changes;
	}

	/**
	 * Returns the sequential ID of the given Change.
	 *
	 * @param Change $rec
	 *
	 * @return int
	 */
	public function getRecordId( $rec ) {
		/* @var Change $rec */
		return $rec->getId();
	}

}

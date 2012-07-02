<?php

namespace Wikibase;
use MWException;

/**
 * Class representing the wb_changes table.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class Changes extends \ORMTable {

	/**
	 * @see IORMTable::getName()
	 * @since 0.1
	 * @return string
	 */
	public function getName() {
		return 'wb_changes';
	}

	/**
	 * @see ORMTable::getFieldPrefix()
	 * @since 0.1
	 * @return string
	 */
	protected function getFieldPrefix() {
		return 'change_';
	}

	/**
	 * @see IORMTable::getRowClass()
	 * @since 0.1
	 * @return string
	 */
	public function getRowClass() {
		return '\Wikibase\Change';
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
			'info' => 'blob',
			'object_id' => 'int',
			'user_id' => 'int',
			'revision_id' => 'int',
		);
	}

	protected static $typeMap = array(
		'item' => 'Wikibase\ItemChange',
	);

	public static function getClassForType( $type ) {
		return array_key_exists( $type, self::$typeMap ) ? self::$typeMap[$type] : 'Wikibase\Change';
	}

	/**
	 * Factory method to construct a new WikibaseChange instance.
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

}
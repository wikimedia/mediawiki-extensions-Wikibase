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
	 * (non-PHPdoc)
	 * @see ORMTable::getName()
	 * @since 0.1
	 * @return string
	 */
	public function getName() {
		return 'wb_changes';
	}

	/**
	 * (non-PHPdoc)
	 * @see ORMTable::getFieldPrefix()
	 * @since 0.1
	 * @return string
	 */
	public function getFieldPrefix() {
		return 'change_';
	}

	/**
	 * (non-PHPdoc)
	 * @see ORMTable::getRowClass()
	 * @since 0.1
	 * @return string
	 */
	public function getRowClass() {
		return 'WikibaseChange';
	}

	/**
	 * (non-PHPdoc)
	 * @see ORMTable::getFields()
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
		'alias' => 'Wikibase\AliasChange',
		'sitelink' => 'Wikibase\SitelinkChange',
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
	public function newFromArray( array $data, $loadDefaults = false ) {
		if ( !array_key_exists( 'type', $data ) ) {
			throw new MWException( 'The type element must be set in the $data array before a new change can be constructed.' );
		}

		$class = static::getClassForType( $data['type'] );

		return new $class( $this, $data, $loadDefaults );
	}

}
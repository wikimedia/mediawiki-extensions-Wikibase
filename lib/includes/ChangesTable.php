<?php

namespace Wikibase;
use MWException;

/**
 * Class representing the wb_changes table.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ChangesTable extends \ORMTable {

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
		return '\Wikibase\ChangeRow';
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

}

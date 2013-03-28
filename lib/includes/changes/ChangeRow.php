<?php

namespace Wikibase;
use \ORMRow, \User;

/**
 * Class representing a single change (ie a row in the wb_changes).
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
class ChangeRow extends ORMRow implements Change {

	/**
	 * Field for caching the linked user.
	 *
	 * @since 0.1
	 * @var User|bool
	 */
	protected $user = false;

	/**
	 * @see Change::getUser
	 *
	 * @since 0.1
	 *
	 * @return User
	 */
	public function getUser() {
		if ( $this->user === false ) {
			$this->user = User::newFromId( $this->getField( 'user_id' ) );
		}

		return $this->user;
	}

	/**
	 * @see Change::getAge
	 *
	 * @since 0.1
	 *
	 * @return integer
	 */
	public function getAge() {
		return time() - (int)wfTimestamp( TS_UNIX, $this->getField( 'time' ) );
	}

	/**
	 * @see Change::getTime
	 *
	 * @since 0.2
	 *
	 * @return string TS_MW
	 */
	public function getTime() {
		return $this->getField( 'time' );
	}

	/**
	 * @see Change::isEmpty
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isEmpty() {
		return false;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.20
	 *
	 * @param \IORMTable $table
	 * @param array|null $fields
	 * @param boolean $loadDefaults
	 */
	public function __construct( \IORMTable $table, $fields = null, $loadDefaults = false ) {
		parent::__construct( $table, $fields, $loadDefaults );

		$this->postConstruct();
	}

	/**
	 * @see ORMRow::getId
	 *
	 * @since 0.2
	 *
	 * @return integer
	 */
	public function getId() {
		return parent::getId();
	}

	/**
	 * @since 0.1
	 */
	protected function postConstruct() {
		if ( !$this->hasField( 'type' ) ) {
			$this->setField( 'type', $this->getType() );
		}
	}

	/**
	 * @see Change::getType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getType() {
		return 'change';
	}

	/**
	 * @see Change::getObjectId
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	public function getObjectId() {
		return $this->getField( 'object_id' );
	}


	/**
	 * @see ORMRow::setField
	 *
	 * @since 0.4
	 *
	 * @param string $name
	 * @param mixed $value
	 *
	 * @throws \MWException
	 */
	public function setField( $name, $value ) {
		if ( $name === 'info' && is_string( $value ) ) {
			$value = $this->unserializeInfo( $value );
		}

		parent::setField( $name, $value );
	}

	/**
	 * @see ORMRow::getWriteValues()
	 *
	 * @todo: remove this once core no longer uses ORMRow::getWriteValues().
	 *        Use ChangesTable::getWriteValues() instead.
	 *
	 * @since 0.4
	 *
	 * @return array
	 */
	protected function getWriteValues() {
		$values = parent::getWriteValues();
		$infoField = $this->table->getPrefixedField( 'info' );

		if ( isset( $values[$infoField] ) ) {
			$values[$infoField] = $this->serializeInfo( $values[$infoField] );
		}

		return $values;
	}

	/**
	 * Serialized the info field using json_encode.
	 * This may be overridden by subclasses to implement special handling
	 * for information in the info field.
	 *
	 * @since 0.4
	 *
	 * @param array $info
	 *
	 * @throws \MWException
	 * @return string
	 */
	public function serializeInfo( array $info ) {
		if ( Settings::get( "changesAsJson" ) === true ) {
			// Make sure we never serialize objects.
			// This is a lot of overhead, so we only do it during testing.
			if ( defined( 'MW_PHPUNIT_TEST' ) ) {
				array_walk_recursive(
					$info,
					function ( $v ) {
						if ( is_object( $v ) ) {
							throw new \MWException( "Refusing to serialize PHP object of type "
								. get_class( $v ) );
						}
 					}
				);
			}

			//XXX: we could JSON_UNESCAPED_UNICODE here, perhaps.
			return json_encode( $info );
		} else {
			// for compatibility with old client code.
			return serialize( $info );
		}
	}

	/**
	 * Unserializes the info field using json_decode.
	 * This may be overridden by subclasses to implement special handling
	 * for information in the info field.
	 *
	 * @since 0.4
	 *
	 * @param string $str
	 *
	 * @return array the info array
	 */
	public function unserializeInfo( $str ) {
		if ( $str[0] === '{' ) { // json
			$info = json_decode( $str, true );
		} else {
			// we may still have legacy stuff in the database for a while!
			$info = unserialize( $str );
		}

		return $info;
	}
}

<?php

namespace Wikibase;

use IORMTable;
use MWException;
use ORMRow;
use User;

/**
 * Class representing a single change (ie a row in the wb_changes).
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
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
	 * @param IORMTable $table
	 * @param array|null $fields
	 * @param boolean $loadDefaults
	 */
	public function __construct( IORMTable $table, $fields = null, $loadDefaults = false ) {
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
	 * @see ORMRow::getField
	 *
	 * Overwritten to unserialize the info field on the fly.
	 *
	 * @since 0.4
	 *
	 * @param string $name Field name
	 * @param $default mixed: Default value to return when none is found
	 * (default: null)
	 *
	 * @throws MWException
	 * @return mixed
	 */
	public function getField( $name, $default = null ) {
		$value = parent::getField( $name, $default );

		if ( $name === 'info' && is_string( $value ) ) {
			$value = $this->unserializeInfo( $value );
		}

		return $value;
	}

	/**
	 * @see ORMRow::getFields
	 *
	 * Overwritten to unserialize the info field on the fly.
	 *
	 * @since 0.4
	 *
	 * @return array
	 */
	public function getFields() {
		$fields = parent::getFields();

		if ( isset( $fields['info'] ) && is_string( $fields['info'] ) ) {
			$fields['info'] = $this->unserializeInfo( $fields['info'] );
		}

		return $fields;
	}

	/**
	 * Returns the info array. The array is deserialized on the fly by getField().
	 * If $cache is set to 'cache', the deserialized version is stored for
	 * later re-use.
	 *
	 * Usually, the deserialized version is not retained to preserve memory when
	 * lots of changes need to be processed. It can however be retained to improve
	 * performance in cases where the same object is accessed several times.
	 *
	 * @param string $cache Set to 'cache' to cache the unserialized version
	 *        of the info array.
	 *
	 * @return array
	 */
	protected function getInfo( $cache = 'no' ) {
		$info = $this->getField( 'info' );

		if ( !is_array( $info ) ) {
			$info = array();
		}

		if ( $cache === 'cache' ) {
			$this->setField( 'info', $info );
		}

		return $info;
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
	 * @throws MWException
	 * @return string
	 */
	public function serializeInfo( array $info ) {
		// Make sure we never serialize objects.
		// This is a lot of overhead, so we only do it during testing.
		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			array_walk_recursive(
				$info,
				function ( $v ) {
					if ( is_object( $v ) ) {
						throw new MWException( "Refusing to serialize PHP object of type "
							. get_class( $v ) );
					}
				}
			);
		}

		//XXX: we could JSON_UNESCAPED_UNICODE here, perhaps.
		return json_encode( $info );
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

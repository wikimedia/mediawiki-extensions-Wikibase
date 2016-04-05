<?php

namespace Wikibase;

use MWException;

/**
 * Class representing a single change (ie a row in the wb_changes).
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class ChangeRow implements Change {

	/**
	 * The fields of the object.
	 * field name (w/o prefix) => value
	 *
	 * @var array
	 */
	private $fields = array( 'id' => null );

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

	public function __construct( array $fields = [] ) {
		$this->setFields( $fields );
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
	 * Overwritten to unserialize the info field on the fly.
	 *
	 * @since 0.4
	 *
	 * @param string $name Field name
	 * @param mixed $default Default value to return when none is found
	 * (default: null)
	 *
	 * @throws MWException
	 * @return mixed
	 */
	public function getField( $name, $default = null ) {
		if ( $this->hasField( $name ) ) {
			$value = $this->fields[$name];
		} elseif ( !is_null( $default ) ) {
			$value = $default;
		} else {
			throw new MWException( 'Attempted to get not-set field ' . $name );
		}

		if ( $name === 'info' && is_string( $value ) ) {
			$value = $this->unserializeInfo( $value );
		}

		return $value;
	}

	/**
	 * Overwritten to unserialize the info field on the fly.
	 *
	 * @since 0.4
	 *
	 * @return array
	 */
	public function getFields() {
		$fields = $this->fields;

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

		if ( $cache === 'cache' ) {
			$this->setField( 'info', $info );
		}

		return $info;
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

		if ( !is_array( $info ) ) {
			wfLogWarning( "Failed to unserializeInfo of id: " . $this->getObjectId() );
			return [];
		}

		return $info;
	}

	/**
	 * Sets the value of a field.
	 * Strings can be provided for other types,
	 * so this method can be called from unserialization handlers.
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function setField( $name, $value ) {
		$this->fields[$name] = $value;
	}

	/**
	 * Sets multiple fields.
	 *
	 * @param array $fields The fields to set
	 * @param bool $override Override already set fields with the provided values?
	 */
	public function setFields( array $fields, $override = true ) {
		foreach ( $fields as $name => $value ) {
			if ( $override || !$this->hasField( $name ) ) {
				$this->setField( $name, $value );
			}
		}
	}

	/**
	 * Returns the objects database id.
	 *
	 * @return int|null
	 */
	public function getId() {
		return $this->getField( 'id' );
	}

	/**
	 * Gets if a certain field is set.
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function hasField( $name ) {
		return array_key_exists( $name, $this->fields );
	}

}

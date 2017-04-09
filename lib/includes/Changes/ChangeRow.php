<?php

namespace Wikibase;

use MWException;

/**
 * Class representing a single change (ie a row in the wb_changes).
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
abstract class ChangeRow implements Change {

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
	 * @return int Seconds
	 */
	public function getAge() {
		return time() - (int)wfTimestamp( TS_UNIX, $this->getField( 'time' ) );
	}

	/**
	 * @see Change::getTime
	 *
	 * @return string TS_MW
	 */
	public function getTime() {
		return $this->getField( 'time' );
	}

	public function __construct( array $fields = array() ) {
		$this->setFields( $fields );
	}

	/**
	 * @see Change::getObjectId
	 *
	 * @return string
	 */
	public function getObjectId() {
		return $this->getField( 'object_id' );
	}

	/**
	 * @param string $name
	 *
	 * @throws MWException
	 * @return mixed
	 */
	public function getField( $name ) {
		if ( !$this->hasField( $name ) ) {
			throw new MWException( 'Attempted to get not-set field ' . $name );
		}

		if ( $name === 'info' ) {
			throw new MWException( 'Use getInfo instead' );
		}

		return $this->fields[$name];
	}

	/**
	 * Overwritten to unserialize the info field on the fly.
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
	 * Returns the info array. The array is deserialized on the fly.
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
	public function getInfo( $cache = 'no' ) {
		$info = $this->hasField( 'info' ) ? $this->fields['info'] : [];

		if ( is_string( $info ) ) {
			$info = $this->unserializeInfo( $info );

			if ( $cache === 'cache' ) {
				$this->setField( 'info', $info );
			}
		}

		return $info;
	}

	/**
	 * @return string JSON
	 */
	abstract public function getSerializedInfo();

	/**
	 * Unserializes the info field using json_decode.
	 * This may be overridden by subclasses to implement special handling
	 * for information in the info field.
	 *
	 * @param string $str
	 *
	 * @return array the info array
	 */
	protected function unserializeInfo( $str ) {
		$info = json_decode( $str, true );

		if ( !is_array( $info ) ) {
			wfLogWarning( "Failed to unserializeInfo of id: " . $this->getObjectId() );
			return array();
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
	 */
	public function setFields( array $fields ) {
		foreach ( $fields as $name => $value ) {
			$this->setField( $name, $value );
		}
	}

	/**
	 * @return int|null Number to be used as an identifier when persisting the change.
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

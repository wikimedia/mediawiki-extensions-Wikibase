<?php

namespace Wikibase\Lib\Changes;

use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Wikimedia\Timestamp\ConvertibleTimestamp;

/**
 * Class representing a single change (ie a row in the wb_changes).
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
abstract class ChangeRow implements Change {

	public const ID = 'id';
	public const METADATA = 'metadata';
	public const INFO = 'info';
	public const TIME = 'time';
	public const USER_ID = 'user_id';
	public const OBJECT_ID = 'object_id';
	public const COMPACT_DIFF = 'compactDiff';
	public const TYPE = 'type';
	public const REVISION_ID = 'revision_id';

	/** @var LoggerInterface */
	protected $logger;

	public function __construct( array $fields = [] ) {
		$this->logger = new NullLogger();
		$this->setFields( $fields );
	}

	public function setLogger( LoggerInterface $logger ): void {
		$this->logger = $logger;
	}

	/**
	 * The fields of the object.
	 * field name (w/o prefix) => value
	 *
	 * @var array
	 */
	private $fields = [ self::ID => null ];

	/**
	 * @see Change::getAge
	 *
	 * @throws Exception if the "time" field is not set
	 * @return int Seconds
	 */
	public function getAge() {
		return time() - (int)ConvertibleTimestamp::convert( TS_UNIX, $this->getField( self::TIME ) );
	}

	/**
	 * @see Change::getTime
	 *
	 * @throws Exception if the "time" field is not set
	 * @return string TS_MW
	 */
	public function getTime() {
		return $this->getField( self::TIME );
	}

	/**
	 * Original (repository) user id, or 0 for logged out users.
	 *
	 * @return int
	 */
	public function getUserId() {
		return $this->hasField( self::USER_ID ) ? $this->getField( self::USER_ID ) : 0;
	}

	/**
	 * @see Change::getObjectId
	 *
	 * @throws Exception if the "object_id" field is not set
	 * @return string
	 */
	public function getObjectId() {
		return $this->getField( self::OBJECT_ID );
	}

	/**
	 * @param string $name
	 *
	 * @throws Exception if the requested field is not set
	 * @return mixed
	 */
	public function getField( $name ) {
		if ( !$this->hasField( $name ) ) {
			throw new Exception( 'Attempted to get not-set field ' . $name );
		}

		if ( $name === self::INFO ) {
			throw new Exception( 'Use getInfo instead' );
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

		if ( isset( $fields[self::INFO] ) && is_string( $fields[self::INFO] ) ) {
			$fields[self::INFO] = $this->unserializeInfo( $fields[self::INFO] );
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
		$info = $this->hasField( self::INFO ) ? $this->fields[self::INFO] : [];

		if ( is_string( $info ) ) {
			$info = $this->unserializeInfo( $info );

			if ( $cache === 'cache' ) {
				$this->setField( self::INFO, $info );
			}
		}

		return $info;
	}

	/**
	 * @param string[] $skipKeys Keys of the info array to skip during serialization. Useful for
	 *        omitting undesired or unserializable data from the serialization.
	 *
	 * @return string JSON
	 */
	abstract public function getSerializedInfo( $skipKeys = [] );

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
			$this->logger->warning( 'Failed to unserializeInfo of id: {id}', [
				self::ID => $this->getObjectId(),
			] );
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
	 */
	public function setFields( array $fields ) {
		foreach ( $fields as $name => $value ) {
			$this->setField( $name, $value );
		}
	}

	/**
	 * @throws Exception if the "id" field is not set
	 * @return int|null Number to be used as an identifier when persisting the change.
	 */
	public function getId() {
		return $this->getField( self::ID );
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

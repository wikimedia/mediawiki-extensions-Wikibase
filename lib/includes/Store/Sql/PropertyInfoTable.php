<?php

namespace Wikibase;

use DBAccessBase;
use DBError;
use InvalidArgumentException;
use ResultWrapper;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * Class PropertyInfoTable implements PropertyInfoStore on top of an SQL table.
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class PropertyInfoTable extends DBAccessBase implements PropertyInfoStore {

	/**
	 * @var string
	 */
	private $tableName;

	/**
	 * @var bool
	 */
	private $isReadonly;

	/**
	 * @param bool $isReadonly Whether the table can be modified.
	 * @param string|bool $wiki The wiki's database to connect to.
	 *        Must be a value LBFactory understands. Defaults to false, which is the local wiki.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $isReadonly, $wiki = false ) {
		if ( !is_bool( $isReadonly ) ) {
			throw new InvalidArgumentException( '$isReadonly must be boolean.' );
		}
		if ( !is_string( $wiki ) && $wiki !== false ) {
			throw new InvalidArgumentException( '$wiki must be a string or false.' );
		}

		parent::__construct( $wiki );
		$this->tableName = 'wb_property_info';
		$this->isReadonly = $isReadonly;
	}

	/**
	 * Decodes an info blob.
	 *
	 * @param string|null|bool $blob
	 *
	 * @return array|null The decoded blob as an associative array, or null if the blob
	 *         could not be decoded.
	 */
	private function decodeBlob( $blob ) {
		if ( $blob === false || $blob === null ) {
			return null;
		}

		$info = json_decode( $blob, true );

		if ( !is_array( $info ) ) {
			$info = null;
		}

		return $info;
	}

	/**
	 * Decodes a result with info blobs.
	 *
	 * @param ResultWrapper $res
	 *
	 * @return array[] The array of decoded blobs
	 */
	private function decodeResult( ResultWrapper $res ) {
		$infos = array();

		foreach ( $res as $row ) {
			$info = $this->decodeBlob( $row->pi_info );

			if ( $info === null ) {
				wfLogWarning( "failed to decode property info blob for property "
					. $row->pi_property_id . ": " . $row->pi_info );
				continue;
			}

			$infos[$row->pi_property_id] = $info;
		}

		return $infos;
	}

	/**
	 * @see PropertyInfoStore::getPropertyInfo
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return array|null
	 * @throws InvalidArgumentException
	 * @throws DBError
	 */
	public function getPropertyInfo( PropertyId $propertyId ) {
		$dbr = $this->getConnection( DB_SLAVE );

		$res = $dbr->selectField(
			$this->tableName,
			'pi_info',
			array( 'pi_property_id' => $propertyId->getNumericId() ),
			__METHOD__
		);

		$this->releaseConnection( $dbr );

		if ( $res === false ) {
			$info = null;
		} else {
			$info = $this->decodeBlob( $res );

			if ( $info === null ) {
				wfLogWarning( "failed to decode property info blob for " . $propertyId . ": " . substr( $res, 0, 200 ) );
			}
		}

		return $info;
	}

	/**
	 * @see PropertyDataTypeLookup::getPropertyInfoForDataType
	 *
	 * @param string $dataType
	 *
	 * @return array[]
	 * @throws DBError
	 */
	public function getPropertyInfoForDataType( $dataType ) {
		$dbr = $this->getConnection( DB_SLAVE );

		$res = $dbr->select(
			$this->tableName,
			array( 'pi_property_id', 'pi_info' ),
			array( 'pi_type' => $dataType ),
			__METHOD__
		);

		$infos = $this->decodeResult( $res );

		$this->releaseConnection( $dbr );

		return $infos;
	}

	/**
	 * @see PropertyInfoStore::getAllPropertyInfo
	 *
	 * @return array[]
	 * @throws DBError
	 */
	public function getAllPropertyInfo() {
		$dbr = $this->getConnection( DB_SLAVE );

		$res = $dbr->select(
			$this->tableName,
			array( 'pi_property_id', 'pi_info' ),
			array(),
			__METHOD__
		);

		$infos = $this->decodeResult( $res );

		$this->releaseConnection( $dbr );

		return $infos;
	}

	/**
	 * @see PropertyInfoStore::setPropertyInfo
	 *
	 * @param PropertyId $propertyId
	 * @param array $info
	 *
	 * @throws DBError
	 * @throws InvalidArgumentException
	 */
	public function setPropertyInfo( PropertyId $propertyId, array $info ) {
		if ( $this->isReadonly ) {
			throw new DBError( 'Cannot write when in readonly mode' );
		}

		if ( !isset( $info[ PropertyInfoStore::KEY_DATA_TYPE ] ) ) {
			throw new InvalidArgumentException( 'Missing required info field: ' . PropertyInfoStore::KEY_DATA_TYPE );
		}

		$type = $info[ PropertyInfoStore::KEY_DATA_TYPE ];
		$json = json_encode( $info );

		$dbw = $this->getConnection( DB_MASTER );

		$dbw->replace(
			$this->tableName,
			array( 'pi_property_id' ),
			array(
				'pi_property_id' => $propertyId->getNumericId(),
				'pi_info' => $json,
				'pi_type' => $type,
			),
			__METHOD__
		);

		$this->releaseConnection( $dbw );
	}

	/**
	 * @see PropertyInfoStore::removePropertyInfo
	 *
	 * @param PropertyId $propertyId
	 *
	 * @throws DBError
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	public function removePropertyInfo( PropertyId $propertyId ) {
		if ( $this->isReadonly ) {
			throw new DBError( 'Cannot write when in readonly mode' );
		}

		$dbw = $this->getConnection( DB_MASTER );

		$dbw->delete(
			$this->tableName,
			array( 'pi_property_id' => $propertyId->getNumericId() ),
			__METHOD__
		);

		$c = $dbw->affectedRows();
		$this->releaseConnection( $dbw );

		return $c > 0;
	}

	/**
	 * Returns a database connection suitable for writing to the database that
	 * contains the property info table.
	 *
	 * This is for use for closely related classes that want to operate directly
	 * on the database table.
	 */
	public function getWriteConnection() {
		return $this->getConnection( DB_MASTER );
	}

	/**
	 * Returns the (logical) name of the database table that contains the property info.
	 *
	 * This is for use for closely related classes that want to operate directly
	 * on the database table.
	 *
	 * @return string
	 */
	public function getTableName() {
		return $this->tableName;
	}

}

<?php

namespace Wikibase\Lib\Store\Sql;

use DBAccessBase;
use InvalidArgumentException;
use Wikibase\DataModel\Assert\RepositoryNameAssert;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\PropertyInfoStore;
use Wikimedia\Assert\Assert;
use Wikimedia\Rdbms\DBError;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * Class PropertyInfoTable implements PropertyInfoStore on top of an SQL table.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class PropertyInfoTable extends DBAccessBase implements PropertyInfoLookup, PropertyInfoStore {

	/**
	 * @var string
	 */
	private $tableName;

	/**
	 * @var EntityIdComposer
	 */
	private $entityIdComposer;

	/**
	 * @var string
	 */
	private $repositoryName;

	/**
	 * @param EntityIdComposer $entityIdComposer
	 * @param string|bool $wiki The wiki's database to connect to.
	 *        Must be a value LBFactory understands. Defaults to false, which is the local wiki.
	 * @param string $repositoryName
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( EntityIdComposer $entityIdComposer, $wiki = false, $repositoryName = '' ) {
		if ( !is_string( $wiki ) && $wiki !== false ) {
			throw new InvalidArgumentException( '$wiki must be a string or false.' );
		}
		RepositoryNameAssert::assertParameterIsValidRepositoryName( $repositoryName, '$repositoryName' );

		parent::__construct( $wiki );
		$this->tableName = 'wb_property_info';
		$this->entityIdComposer = $entityIdComposer;
		$this->repositoryName = $repositoryName;
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
	 * @param IResultWrapper $res
	 *
	 * @return array[] The array of decoded blobs
	 */
	private function decodeResult( IResultWrapper $res ) {
		$infos = [];

		foreach ( $res as $row ) {
			$info = $this->decodeBlob( $row->pi_info );

			if ( $info === null ) {
				wfLogWarning( "failed to decode property info blob for property "
					. $row->pi_property_id . ": " . $row->pi_info );
				continue;
			}

			$id = $this->entityIdComposer->composeEntityId(
				$this->repositoryName,
				Property::ENTITY_TYPE,
				$row->pi_property_id
			);
			$infos[$id->getSerialization()] = $info;
		}

		return $infos;
	}

	/**
	 * @see PropertyInfoLookup::getPropertyInfo
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return array|null
	 * @throws InvalidArgumentException
	 * @throws DBError
	 */
	public function getPropertyInfo( PropertyId $propertyId ) {
		$this->assertPropertyIdFromCorrectRepository( $propertyId );

		$dbr = $this->getConnection( DB_REPLICA );

		$res = $dbr->selectField(
			$this->tableName,
			'pi_info',
			[ 'pi_property_id' => $propertyId->getNumericId() ],
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
	 * @see PropertyInfoLookup::getPropertyInfoForDataType
	 *
	 * @param string $dataType
	 *
	 * @return array[] Array containing serialized property IDs as keys and info arrays as values
	 * @throws DBError
	 */
	public function getPropertyInfoForDataType( $dataType ) {
		$dbr = $this->getConnection( DB_REPLICA );

		$res = $dbr->select(
			$this->tableName,
			[ 'pi_property_id', 'pi_info' ],
			[ 'pi_type' => $dataType ],
			__METHOD__
		);

		$infos = $this->decodeResult( $res );

		$this->releaseConnection( $dbr );

		return $infos;
	}

	/**
	 * @see PropertyInfoLookup::getAllPropertyInfo
	 *
	 * @return array[] Array containing serialized property IDs as keys and info arrays as values
	 * @throws DBError
	 */
	public function getAllPropertyInfo() {
		$dbr = $this->getConnection( DB_REPLICA );

		$res = $dbr->select(
			$this->tableName,
			[ 'pi_property_id', 'pi_info' ],
			[],
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
		if ( !isset( $info[ PropertyInfoLookup::KEY_DATA_TYPE ] ) ) {
			throw new InvalidArgumentException( 'Missing required info field: ' . PropertyInfoLookup::KEY_DATA_TYPE );
		}

		$this->assertPropertyIdFromCorrectRepository( $propertyId );

		$type = $info[ PropertyInfoLookup::KEY_DATA_TYPE ];
		$json = json_encode( $info );

		$dbw = $this->getConnection( DB_MASTER );

		$dbw->replace(
			$this->tableName,
			[ 'pi_property_id' ],
			[
				'pi_property_id' => $propertyId->getNumericId(),
				'pi_info' => $json,
				'pi_type' => $type,
			],
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
		$this->assertPropertyIdFromCorrectRepository( $propertyId );

		$dbw = $this->getConnection( DB_MASTER );

		$dbw->delete(
			$this->tableName,
			[ 'pi_property_id' => $propertyId->getNumericId() ],
			__METHOD__
		);

		$c = $dbw->affectedRows();
		$this->releaseConnection( $dbw );

		return $c > 0;
	}

	private function assertPropertyIdFromCorrectRepository( PropertyId $id ) {
		$repository = $id->getRepositoryName();

		Assert::parameter(
			$repository === $this->repositoryName,
			'$propertyId',
			"The property id's repository name ($repository) must match the PropertyInfoTable's ($this->repositoryName)"
		);
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

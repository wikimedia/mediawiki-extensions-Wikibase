<?php

namespace Wikibase\Lib\Store\Sql;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\PropertyInfoStore;
use Wikimedia\Assert\Assert;
use Wikimedia\Rdbms\DBError;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * Class PropertyInfoTable implements PropertyInfoStore on top of an SQL table.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class PropertyInfoTable implements PropertyInfoLookup, PropertyInfoStore {

	private const TABLE_NAME = 'wb_property_info';

	/** @var EntityIdComposer */
	private $entityIdComposer;

	/**
	 * @var RepoDomainDb
	 */
	private $db;

	/** @var bool */
	private $allowWrites;

	/**
	 * @param EntityIdComposer $entityIdComposer
	 * @param RepoDomainDb $db
	 * @param bool $allowWrites Should writes be allowed to the table? false in cases that a remote property source is being used.
	 *
	 * TODO split this more cleanly into a lookup and a writer, and then $allowWrites would not be needed?
	 */
	public function __construct(
		EntityIdComposer $entityIdComposer,
		RepoDomainDb $db,
		bool $allowWrites
	) {
		$this->entityIdComposer = $entityIdComposer;
		$this->db = $db;
		$this->allowWrites = $allowWrites;
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
				'',
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
		Assert::parameterType( NumericPropertyId::class, $propertyId, '$propertyId' );
		/** @var NumericPropertyId $propertyId */
		'@phan-var NumericPropertyId $propertyId';

		$dbr = $this->getReadConnection();

		$res = $dbr->newSelectQueryBuilder()
			->select( 'pi_info' )
			->from( self::TABLE_NAME )
			->where( [ 'pi_property_id' => $propertyId->getNumericId() ] )
			->caller( __METHOD__ )
			->fetchField();

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
		$dbr = $this->getReadConnection();

		$res = $dbr->newSelectQueryBuilder()
			->select( [ 'pi_property_id', 'pi_info' ] )
			->from( self::TABLE_NAME )
			->where( [ 'pi_type' => $dataType ] )
			->caller( __METHOD__ )
			->fetchResultSet();

		$infos = $this->decodeResult( $res );

		return $infos;
	}

	/**
	 * @see PropertyInfoLookup::getAllPropertyInfo
	 *
	 * @return array[] Array containing serialized property IDs as keys and info arrays as values
	 * @throws DBError
	 */
	public function getAllPropertyInfo() {
		$dbr = $this->getReadConnection();

		$res = $dbr->newSelectQueryBuilder()
			->select( [ 'pi_property_id', 'pi_info' ] )
			->from( self::TABLE_NAME )
			->caller( __METHOD__ )
			->fetchResultSet();

		$infos = $this->decodeResult( $res );

		return $infos;
	}

	/**
	 * @see PropertyInfoStore::setPropertyInfo
	 *
	 * @param NumericPropertyId $propertyId
	 * @param array $info
	 *
	 * @throws DBError
	 * @throws InvalidArgumentException
	 */
	public function setPropertyInfo( NumericPropertyId $propertyId, array $info ) {
		if ( !isset( $info[ PropertyInfoLookup::KEY_DATA_TYPE ] ) ) {
			throw new InvalidArgumentException( 'Missing required info field: ' . PropertyInfoLookup::KEY_DATA_TYPE );
		}

		$this->assertCanWritePropertyInfo();

		$type = $info[ PropertyInfoLookup::KEY_DATA_TYPE ];
		$json = json_encode( $info );

		$dbw = $this->getWriteConnection();

		$dbw->replace(
			self::TABLE_NAME,
			'pi_property_id',
			[
				'pi_property_id' => $propertyId->getNumericId(),
				'pi_info' => $json,
				'pi_type' => $type,
			],
			__METHOD__
		);
	}

	/**
	 * @see PropertyInfoStore::removePropertyInfo
	 *
	 * @param NumericPropertyId $propertyId
	 *
	 * @throws DBError
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	public function removePropertyInfo( NumericPropertyId $propertyId ) {
		$this->assertCanWritePropertyInfo();

		$dbw = $this->getWriteConnection();

		$dbw->delete(
			self::TABLE_NAME,
			[ 'pi_property_id' => $propertyId->getNumericId() ],
			__METHOD__
		);

		$c = $dbw->affectedRows();

		return $c > 0;
	}

	private function assertCanWritePropertyInfo(): void {
		if ( !$this->allowWrites ) {
			throw new InvalidArgumentException(
				'This implementation cannot be used to write data to non-local database'
			);
		}
	}

	private function getWriteConnection(): IDatabase {
		return $this->db->connections()->getWriteConnection();
	}

	private function getReadConnection(): IDatabase {
		return $this->db->connections()->getReadConnection();
	}

	/**
	 * Returns a database wrapper suitable for working with the database that
	 * contains the property info table.
	 *
	 * This is for use by closely related classes that want to operate directly
	 * on the database table.
	 */
	public function getDomainDb(): RepoDomainDb {
		return $this->db;
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
		return self::TABLE_NAME;
	}

}

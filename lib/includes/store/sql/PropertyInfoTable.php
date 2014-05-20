<?php

namespace Wikibase;

use DatabaseUpdater;
use DBAccessBase;
use DBError;
use HashBagOStuff;
use InvalidArgumentException;
use ObservableMessageReporter;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\CachingEntityRevisionLookup;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\WikiPageEntityLookup;

/**
 * Class PropertyInfoTable implements PropertyInfoStore on top of an SQL table.
 *
 * @since 0.4
 *
 * @license GPL 2+
 * @author Daniel Kinzler
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
	 */
	public function __construct( $isReadonly, $wiki = false ) {
		assert( is_bool( $isReadonly ) );
		assert( is_string( $wiki ) || $wiki === false );

		$this->tableName = 'wb_property_info';
		$this->isReadonly = $isReadonly;
		$this->wiki = $wiki;
	}

	/**
	 * Register the updates needed for creating the appropriate table(s).
	 *
	 * @param DatabaseUpdater $updater
	 */
	public static function registerDatabaseUpdates( DatabaseUpdater $updater ) {
		$table = 'wb_property_info';

		if ( !$updater->tableExists( $table ) ) {
			$type = $updater->getDB()->getType();
			$fileBase = __DIR__ . '/' . $table;

			$file = $fileBase . '.' . $type . '.sql';
			if ( !file_exists( $file ) ) {
				$file = $fileBase . '.sql';
			}

			$updater->addExtensionTable( $table, $file );

			// populate the table after creating it
			$updater->addExtensionUpdate( array(
				array( 'Wikibase\PropertyInfoTable', 'rebuildPropertyInfo' )
			) );
		}
	}

	/**
	 * Wrapper for invoking PropertyInfoTableBuilder from DatabaseUpdater
	 * during a database update.
	 *
	 * @param DatabaseUpdater $updater
	 */
	public static function rebuildPropertyInfo( DatabaseUpdater $updater ) {
		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback(
			function ( $msg ) use ( $updater ) {
				$updater->output( "..." . $msg . "\n" );
			}
		);

		$table = new PropertyInfoTable( false );
		$contentCodec = new EntityContentDataCodec();
		$entityFactory = new EntityFactory( array( Property::ENTITY_TYPE => '\Wikibase\Property' ) );
		$wikiPageEntityLookup = new WikiPageEntityLookup( $contentCodec, $entityFactory, false );
		$cachingEntityLookup = new CachingEntityRevisionLookup( $wikiPageEntityLookup, new \HashBagOStuff() );

		$builder = new PropertyInfoTableBuilder( $table, $cachingEntityLookup );
		$builder->setReporter( $reporter );
		$builder->setUseTransactions( false );

		$updater->output( 'Populating ' . $table->getTableName() . "\n" );
		$builder->rebuildPropertyInfo();
	}

	/**
	 * @see PropertyInfoStore::getPropertyInfo
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return array|null
	 *
	 * @throws InvalidArgumentException
	 * @throws DBError
	 */
	public function getPropertyInfo( PropertyId $propertyId ) {
		wfProfileIn( __METHOD__ );

		$dbw = $this->getConnection( DB_SLAVE );

		$res = $dbw->selectField(
			$this->tableName,
			'pi_info',
			array( 'pi_property_id' => $propertyId->getNumericId() ),
			__METHOD__
		);

		$this->releaseConnection( $dbw );

		if ( $res === false ) {
			$info = null;
		} else {
			$info = $this->decodeInfo( $res );

			if ( $info === null ) {
				wfLogWarning( "failed to decode property info blob for " . $propertyId . ": " . substr( $res, 0, 200 ) );
			}
		}

		wfProfileOut( __METHOD__ );
		return $info;
	}

	/**
	 * Decodes an info blob.
	 *
	 * @param string|null|bool  $blob
	 *
	 * @return array|null The decoded blob as an associative array, or null if the blob
	 *         could not be decoded.
	 */
	protected function decodeInfo( $blob ) {
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
	 * @see   PropertyInfoStore::getAllPropertyInfo
	 *
	 * @return array[]
	 *
	 * @throws DBError
	 */
	public function getAllPropertyInfo() {
		wfProfileIn( __METHOD__ );
		$dbw = $this->getConnection( DB_SLAVE );

		$res = $dbw->select(
			$this->tableName,
			array( 'pi_property_id', 'pi_info' ),
			array(),
			__METHOD__
		);

		$infos = array();

		while ( $row = $res->fetchObject() ) {
			$info = $this->decodeInfo( $row->pi_info );

			if ( $info === null ) {
				wfLogWarning( "failed to decode property info blob for property "
					. $row->pi_property_id . ": " . $row->pi_info );
				continue;
			}

			$infos[$row->pi_property_id] = $info;
		}

		$this->releaseConnection( $dbw );

		wfProfileOut( __METHOD__ );
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

		if ( !isset( $info[ PropertyInfoStore::KEY_DATA_TYPE ]) ) {
			throw new InvalidArgumentException( 'Missing required info field: ' . PropertyInfoStore::KEY_DATA_TYPE );
		}

		wfProfileIn( __METHOD__ );

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

		wfProfileOut( __METHOD__ );
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

		wfProfileIn( __METHOD__ );
		$dbw = $this->getConnection( DB_MASTER );

		$dbw->delete(
			$this->tableName,
			array( 'pi_property_id' => $propertyId->getNumericId() ),
			__METHOD__
		);

		$c = $dbw->affectedRows();
		$this->releaseConnection( $dbw );

		wfProfileOut( __METHOD__ );
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

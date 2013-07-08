<?php
 /**
 *
 * Copyright © 26.06.13 by the authors listed below.
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
 * @license GPL 2+
 * @file
 * @ingroup WikibaseLib
 *
 * @author Daniel Kinzler
 */


namespace Wikibase;


use DBError;

/**
 * Class PropertyInfoTable implemnents PropertyInfoStore on top of an SQL table.
 *
 * @since 0.4
 *
 * @package Wikibase
 */
class PropertyInfoTable extends \DBAccessBase implements PropertyInfoStore {

	/**
	 * @var string
	 */
	protected $table;

	/**
	 * @var bool
	 */
	protected $readonly;

	/**
	 * @param string $table The table to use
	 * @param bool $readonly Whether the table can be modified.
	 * @param string|bool $wiki The wiki's database to connect to.
	 *        Must be a value LBFactory understands. Defaults to false, which is the local wiki.
	 */
	public function __construct( $readonly, $wiki = false ) {
		assert( is_bool( $readonly ) );
		assert( is_string( $wiki ) || $wiki === false );

		$this->table = 'wb_property_info';
		$this->readonly = $readonly;
		$this->wiki = $wiki;
	}

	/**
	 * Register the updates needed for creating the appropriate table(s).
	 *
	 * @param \DatabaseUpdater $updater
	 */
	public static function registerDatabaseUpdates( \DatabaseUpdater $updater ) {
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
	 * @param \DatabaseUpdater $updater
	 */
	public static function rebuildPropertyInfo( \DatabaseUpdater $updater ) {
		$reporter = new \ObservableMessageReporter();
		$reporter->registerReporterCallback(
			function ( $msg ) use ( $updater ) {
				$updater->output( "..." . $msg . "\n" );
			}
		);

		$table = new PropertyInfoTable( false );
		$entityLookup = new WikiPageEntityLookup( false );

		$builder = new PropertyInfoTableBuilder( $table, $entityLookup );
		$builder->setReporter( $reporter );
		$builder->setUseTransactions( false );

		$updater->output( 'Populating ' . $table->getTableName() . "\n" );
		$builder->rebuildPropertyInfo();
	}

	/**
	 * @see   PropertyInfoStore::getPropertyInfo
	 *
	 * @param EntityId $propertyId
	 *
	 * @return array|null
	 *
	 * @throws \InvalidArgumentException
	 * @throws \DBError
	 */
	public function getPropertyInfo( EntityId $propertyId ) {
		if ( $propertyId->getEntityType() !== Property::ENTITY_TYPE ) {
			throw new \InvalidArgumentException( 'Property ID expected! ' . $propertyId );
		}

		wfProfileIn( __METHOD__ );

		$dbw = $this->getConnection( DB_SLAVE );

		$res = $dbw->selectField(
			$this->table,
			'pi_info',
			array( 'pi_property_id' => $propertyId->getNumericId() ),
			__METHOD__
		);

		$this->releaseConnection( $dbw );

		if ( $res === false ) {
			$info = null;
		} else {
			$info = json_decode( $res, true );

			if ( $info === null ) {
				wfLogWarning( "failed to decode property info blob for " . $propertyId . ": " . $res );
			}
		}

		wfProfileOut( __METHOD__ );
		return $info;
	}

	/**
	 * @see   PropertyInfoStore::getAllPropertyInfo
	 *
	 * @return array[]
	 *
	 * @throws \DBError
	 */
	public function getAllPropertyInfo() {
		wfProfileIn( __METHOD__ );
		$dbw = $this->getConnection( DB_SLAVE );

		$res = $dbw->select(
			$this->table,
			array( 'pi_property_id', 'pi_info' ),
			array(),
			__METHOD__
		);

		$infos = array();

		while ( $row = $res->fetchObject() ) {
			$info = json_decode( $row->pi_info );

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
	 * @param EntityId $propertyId
	 * @param array    $info
	 *
	 * @throws \DBError
	 * @throws \InvalidArgumentException
	 */
	public function setPropertyInfo( EntityId $propertyId, array $info ) {
		if ( $propertyId->getEntityType() !== Property::ENTITY_TYPE ) {
			throw new \InvalidArgumentException( 'Property ID expected! ' . $propertyId );
		}

		if ( $this->readonly ) {
			throw new DBError( 'Cannot write when in readonly mode' );
		}

		if ( !isset( $info[ PropertyInfoStore::KEY_DATA_TYPE ]) ) {
			throw new \InvalidArgumentException( 'Missing required info field: ' . PropertyInfoStore::KEY_DATA_TYPE );
		}

		wfProfileIn( __METHOD__ );

		$type = $info[ PropertyInfoStore::KEY_DATA_TYPE ];
		$json = json_encode( $info );

		$dbw = $this->getConnection( DB_MASTER );

		$dbw->replace(
			$this->table,
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
	 * @see   PropertyInfoStore::removePropertyInfo
	 *
	 * @param EntityId $propertyId
	 *
	 * @throws DBError
	 * @throws \InvalidArgumentException
	 * @return bool
	 */
	public function removePropertyInfo( EntityId $propertyId ) {
		if ( $propertyId->getEntityType() !== Property::ENTITY_TYPE ) {
			throw new \InvalidArgumentException( 'Property ID expected! ' . $propertyId );
		}

		if ( $this->readonly ) {
			throw new DBError( 'Cannot write when in readonly mode' );
		}

		wfProfileIn( __METHOD__ );
		$dbw = $this->getConnection( DB_MASTER );

		$dbw->delete(
			$this->table,
			array( 'pi_property_id' => $propertyId->getNumericId() ),
			__METHOD__
		);

		$c = $dbw->affectedRows();
		$this->releaseConnection( $dbw );

		wfProfileOut( __METHOD__ );
		return $c > 0 ? true : false;
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
		return $this->table;
	}
}
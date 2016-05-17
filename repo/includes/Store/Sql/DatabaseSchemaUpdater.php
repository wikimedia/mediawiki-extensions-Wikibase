<?php

namespace Wikibase\Repo\Store\Sql;

use DatabaseBase;
use DatabaseUpdater;
use HashBagOStuff;
use MWException;
use Wikibase\RebuildTermsSearchKey;
use Wikibase\Repo\Maintenance\RebuildEntityPerPage;
use Wikibase\Store;
use Wikibase\PropertyInfoTable;
use Wikibase\PropertyInfoTableBuilder;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikibase\Lib\Store\CachingEntityRevisionLookup;
use Wikibase\Lib\Store\RevisionBasedEntityLookup;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup;
use Wikibase\Lib\Store\WikiPageEntityRevisionLookup;
use Wikibase\Repo\WikibaseRepo;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Marius Hoch
 */
class DatabaseSchemaUpdater {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	private static function newFromGlobalState() {
		$store = WikibaseRepo::getDefaultInstance()->getStore();

		return new self( $store );
	}

	/**
	 * Schema update to set up the needed database tables.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
	 *
	 * @since 0.5
	 *
	 * @param DatabaseUpdater $updater
	 *
	 * @return bool
	 */
	public static function onSchemaUpdate( DatabaseUpdater $updater ) {
		$schemaUpdater = self::newFromGlobalState();
		$schemaUpdater->doSchemaUpdate( $updater );

		return true;
	}

	/**
	 * @param DatabaseUpdater $updater
	 */
	public function doSchemaUpdate( DatabaseUpdater $updater ) {
		$db = $updater->getDB();
		$type = $db->getType();

		if ( $type !== 'mysql' && $type !== 'sqlite' ) {
			wfWarn( "Database type '$type' is not supported by the Wikibase repository." );
			return;
		}

		$this->addChangesTable( $updater, $type );

		// Update from 0.1.
		if ( !$db->tableExists( 'wb_terms' ) ) {
			$updater->dropTable( 'wb_items_per_site' );
			$updater->dropTable( 'wb_items' );
			$updater->dropTable( 'wb_aliases' );
			$updater->dropTable( 'wb_texts_per_lang' );

			$updater->addExtensionTable(
				'wb_terms',
				$this->getUpdateScriptPath( 'Wikibase', $db->getType() )
			);

			$this->store->rebuild();
		}

		$this->updateEntityPerPageTable( $updater, $db );
		$this->updateTermsTable( $updater, $db );
		$this->updateItemsPerSiteTable( $updater, $db );
		$this->updateChangesTable( $updater, $db );

		$this->registerPropertyInfoTableUpdates( $updater );
	}

	/**
	 * @param DatabaseUpdater $updater
	 * @param string $type
	 */
	private function addChangesTable( DatabaseUpdater $updater, $type ) {
		$updater->addExtensionTable(
			'wb_changes',
			$this->getUpdateScriptPath( 'changes', $type )
		);

		if ( $type === 'mysql' && !$updater->updateRowExists( 'ChangeChangeObjectId.sql' ) ) {
			$updater->addExtensionUpdate( array(
				'applyPatch',
				$this->getUpdateScriptPath( 'ChangeChangeObjectId', $type ),
				true
			) );

			$updater->insertUpdateRow( 'ChangeChangeObjectId.sql' );
		}

		$updater->addExtensionTable(
			'wb_changes_dispatch',
			$this->getUpdateScriptPath( 'changes_dispatch', $type )
		);
	}

	/**
	 * @param DatabaseUpdater $updater
	 * @param DatabaseBase $db
	 */
	private function updateItemsPerSiteTable( DatabaseUpdater $updater, DatabaseBase $db ) {
		// Make wb_items_per_site.ips_site_page VARCHAR(310) - T99459
		// NOTE: this update doesn't work on SQLite, but it's not needed there anyway.
		if ( $db->getType() !== 'sqlite' ) {
			$updater->modifyExtensionField(
				'wb_items_per_site',
				'ips_site_page',
				$this->getUpdateScriptPath( 'MakeIpsSitePageLarger', $db->getType() )
			);
		}
	}

	/**
	 * @param DatabaseUpdater $updater
	 * @param DatabaseBase $db
	 */
	private function updateChangesTable( DatabaseUpdater $updater, DatabaseBase $db ) {
		// Make wb_changes.change_info MEDIUMBLOB - T108246
		// NOTE: this update doesn't work on SQLite, but it's not needed there anyway.
		if ( $db->getType() !== 'sqlite' ) {
			$updater->modifyExtensionField(
				'wb_changes',
				'change_info',
				$this->getUpdateScriptPath( 'MakeChangeInfoLarger', $db->getType() )
			);
		}
	}

	private function registerPropertyInfoTableUpdates( DatabaseUpdater $updater ) {
		$table = 'wb_property_info';

		if ( !$updater->tableExists( $table ) ) {
			$type = $updater->getDB()->getType();
			$fileBase = __DIR__ . '/../../../sql/' . $table;

			$file = $fileBase . '.' . $type . '.sql';
			if ( !file_exists( $file ) ) {
				$file = $fileBase . '.sql';
			}

			$updater->addExtensionTable( $table, $file );

			// populate the table after creating it
			$updater->addExtensionUpdate( array(
				array( __CLASS__, 'rebuildPropertyInfo' )
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
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$contentCodec = $wikibaseRepo->getEntityContentDataCodec();
		$propertyInfoBuilder = $wikibaseRepo->newPropertyInfoBuilder();

		$wikiPageEntityLookup = new WikiPageEntityRevisionLookup(
			$contentCodec,
			new WikiPageEntityMetaDataLookup( $wikibaseRepo->getEntityNamespaceLookup() ),
			false
		);

		$cachingEntityLookup = new CachingEntityRevisionLookup( $wikiPageEntityLookup, new HashBagOStuff() );
		$entityLookup = new RevisionBasedEntityLookup( $cachingEntityLookup );

		$builder = new PropertyInfoTableBuilder( $table, $entityLookup, $propertyInfoBuilder );
		$builder->setReporter( $reporter );
		$builder->setUseTransactions( false );

		$updater->output( 'Populating ' . $table->getTableName() . "\n" );
		$builder->rebuildPropertyInfo();
	}

	/**
	 * Returns the script directory that contains a file with the given name.
	 *
	 * @param string $fileName with extension
	 *
	 * @throws MWException If the file was not found in any script directory
	 * @return string The directory that contains the file
	 */
	private function getUpdateScriptDir( $fileName ) {
		$dirs = array(
			__DIR__,
			__DIR__ . '/../../../sql'
		);

		foreach ( $dirs as $dir ) {
			if ( file_exists( "$dir/$fileName" ) ) {
				return $dir;
			}
		}

		throw new MWException( "Update script not found: $fileName" );
	}

	/**
	 * Returns the appropriate script file for use with the given database type.
	 * Searches for files with type-specific extensions in the script directories,
	 * falling back to the plain ".sql" extension if no specific script is found.
	 *
	 * @param string $name the script's name, without file extension
	 * @param string $type the database type, as returned by DatabaseBase::getType()
	 *
	 * @return string The path to the script file
	 * @throws MWException If the script was not found in any script directory
	 */
	private function getUpdateScriptPath( $name, $type ) {
		$extensions = array(
			'sqlite' => 'sqlite.sql',
			//'postgres' => 'pg.sql', // PG support is broken as of Dec 2013
			'mysql' => 'mysql.sql',
		);

		// Find the base directory by looking for a plain ".sql" file.
		$dir = $this->getUpdateScriptDir( "$name.sql" );

		if ( isset( $extensions[$type] ) ) {
			$extension = $extensions[$type];
			$path = "$dir/$name.$extension";

			// if a type-specific file exists, use it
			if ( file_exists( "$dir/$name.$extension" ) ) {
				return $path;
			}
		} else {
			throw new MWException( "Database type $type is not supported by Wikibase!" );
		}

		// we already know that the generic file exists
		$path = "$dir/$name.sql";
		return $path;
	}

	/**
	 * Applies updates to the wb_entity_per_page table.
	 *
	 * @param DatabaseUpdater $updater
	 * @param DatabaseBase $db
	 */
	private function updateEntityPerPageTable( DatabaseUpdater $updater, DatabaseBase $db ) {
		// Update from 0.1. or 0.2.
		if ( !$db->tableExists( 'wb_entity_per_page' ) ) {
			$updater->addExtensionTable(
				'wb_entity_per_page',
				$this->getUpdateScriptPath( 'AddEntityPerPage', $db->getType() )
			);

			$updater->addPostDatabaseUpdateMaintenance( RebuildEntityPerPage::class );
		} else {
			$updater->addExtensionField(
				'wb_entity_per_page',
				'epp_redirect_target',
				$this->getUpdateScriptPath( 'AddEppRedirectTarget', $db->getType() )
			);
		}
	}

	/**
	 * Applies updates to the wb_terms table.
	 *
	 * @param DatabaseUpdater $updater
	 * @param DatabaseBase $db
	 */
	private function updateTermsTable( DatabaseUpdater $updater, DatabaseBase $db ) {
		// ---- Update from 0.1 or 0.2. ----
		if ( !$db->fieldExists( 'wb_terms', 'term_search_key' ) ) {
			$updater->addExtensionField(
				'wb_terms',
				'term_search_key',
				$this->getUpdateScriptPath( 'AddTermsSearchKey', $db->getType() )
			);

			$updater->addPostDatabaseUpdateMaintenance( RebuildTermsSearchKey::class );
		}

		// creates wb_terms.term_row_id
		// and also wb_item_per_site.ips_row_id.
		$updater->addExtensionField(
			'wb_terms',
			'term_row_id',
			$this->getUpdateScriptPath( 'AddRowIDs', $db->getType() )
		);

		// add weight to wb_terms
		$updater->addExtensionField(
			'wb_terms',
			'term_weight',
			$this->getUpdateScriptPath( 'AddTermsWeight', $db->getType() )
		);

		// ---- Update from 0.4 ----

		// NOTE: this update doesn't work on SQLite, but it's not needed there anyway.
		if ( $db->getType() !== 'sqlite' ) {
			// make term_row_id BIGINT
			$updater->modifyExtensionField(
				'wb_terms',
				'term_row_id',
				$this->getUpdateScriptPath( 'MakeRowIDsBig', $db->getType() )
			);
		}

		// updated indexes
		$updater->addExtensionIndex(
			'wb_terms',
			'term_search',
			$this->getUpdateScriptPath( 'UpdateTermIndexes', $db->getType() )
		);
	}

}

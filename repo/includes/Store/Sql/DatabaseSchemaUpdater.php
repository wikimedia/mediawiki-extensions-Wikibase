<?php

namespace Wikibase\Repo\Store\Sql;

use DatabaseUpdater;
use HashBagOStuff;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;
use MediaWiki\MediaWikiServices;
use MWException;
use Onoi\MessageReporter\ObservableMessageReporter;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LegacyAdapterItemLookup;
use Wikibase\DataModel\Services\Lookup\LegacyAdapterPropertyLookup;
use Wikibase\Lib\Store\CachingEntityRevisionLookup;
use Wikibase\Lib\Store\EntityRevisionCache;
use Wikibase\Lib\Store\RevisionBasedEntityLookup;
use Wikibase\Lib\Store\Sql\EntityIdLocalPartPageTableEntityQuery;
use Wikibase\Lib\Store\Sql\PropertyInfoTable;
use Wikibase\Lib\Store\Sql\WikiPageEntityDataLoader;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup;
use Wikibase\Lib\Store\Sql\WikiPageEntityRevisionLookup;
use Wikibase\Repo\RangeTraversable;
use Wikibase\Repo\Store\ItemTermsRebuilder;
use Wikibase\Repo\Store\PropertyTermsRebuilder;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Rdbms\IDatabase;

/**
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Marius Hoch
 */
class DatabaseSchemaUpdater implements LoadExtensionSchemaUpdatesHook {

	/**
	 * Schema update to set up the needed database tables.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
	 *
	 * @param DatabaseUpdater $updater
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$db = $updater->getDB();
		$type = $db->getType();

		if ( $type !== 'mysql' && $type !== 'sqlite' && $type !== 'postgres' ) {
			wfWarn( "Database type '$type' is not supported by the Wikibase repository." );
			return;
		}

		$this->addChangesTable( $updater, $type );

		if ( $db->tableExists( 'wb_aliases', __METHOD__ ) ) {
			// Update from 0.1.
			$updater->dropExtensionTable( 'wb_items_per_site' );
			$updater->dropExtensionTable( 'wb_items' );
			$updater->dropExtensionTable( 'wb_aliases' );
			$updater->dropExtensionTable( 'wb_texts_per_lang' );
		}

		$updater->addExtensionTable(
			'wb_id_counters',
			$this->getScriptPath( 'wb_id_counters', $db->getType() )
		);
		$updater->addExtensionTable(
			'wb_items_per_site',
			$this->getScriptPath( 'wb_items_per_site', $db->getType() )
		);
		// NOTE: This update is neither needed nor does it work on SQLite or Postgres.
		if ( $db->getType() === 'mysql' ) {
			// make ips_row_id BIGINT
			$updater->modifyExtensionField(
				'wb_items_per_site',
				'ips_row_id',
				$this->getUpdateScriptPath( 'MakeRowIDsBig', $db->getType() )
			);
		}

		$this->updateItemsPerSiteTable( $updater, $db );
		$this->updateChangesTable( $updater, $db );

		$this->registerPropertyInfoTableUpdates( $updater );

		if ( $db->tableExists( 'wb_entity_per_page', __METHOD__ ) ) {
			$updater->dropExtensionTable( 'wb_entity_per_page' );
		}

		$updater->addExtensionTable(
			'wbt_text',
			$this->getScriptPath( 'term_store', $db->getType() )
		);
		if ( !$updater->updateRowExists( __CLASS__ . '::rebuildPropertyTerms' ) ) {
			$updater->addExtensionUpdate( [
				[ __CLASS__, 'rebuildPropertyTerms' ]
			] );
		}
		if ( !$updater->updateRowExists( __CLASS__ . '::rebuildItemTerms' ) ) {
			$updater->addExtensionUpdate( [
				[ __CLASS__, 'rebuildItemTerms' ]
			] );
		}

		$updater->dropExtensionTable( 'wb_terms' );

		$this->updateChangesSubscriptionTable( $updater );

		$updater->dropExtensionIndex(
			'wb_changes',
			'wb_changes_change_type',
			$this->getUpdateScriptPath( 'patch-wb_changes-drop-change_type_index', $db->getType() )
		);

		$updater->modifyExtensionField(
			'wb_changes_dispatch',
			'chd_seen',
			$this->getUpdateScriptPath( 'patch-wb_changes_dispatch-make-chd_seen-unsigned', $db->getType() )
		);
	}

	private function updateChangesSubscriptionTable( DatabaseUpdater $dbUpdater ): void {
		$table = 'wb_changes_subscription';

		if ( !$dbUpdater->tableExists( $table ) ) {
			$db = $dbUpdater->getDB();
			$script = $this->getScriptPath( 'wb_changes_subscription', $db->getType() );
			$dbUpdater->addExtensionTable( $table, $script );

			// Register function for populating the table.
			// Note that this must be done with a static function,
			// for reasons that do not need explaining at this juncture.
			$dbUpdater->addExtensionUpdate( [
				[ __CLASS__, 'fillSubscriptionTable' ],
				$table
			] );
		}
	}

	/**
	 * @param DatabaseUpdater $updater
	 * @param string $type
	 */
	private function addChangesTable( DatabaseUpdater $updater, $type ) {
		$updater->addExtensionTable(
			'wb_changes',
			$this->getScriptPath( 'wb_changes', $type )
		);

		if ( $type === 'mysql' && !$updater->updateRowExists( 'ChangeChangeObjectId.sql' ) ) {
			$updater->addExtensionUpdate( [
				'applyPatch',
				$this->getUpdateScriptPath( 'ChangeChangeObjectId', $type ),
				true
			] );

			$updater->insertUpdateRow( 'ChangeChangeObjectId.sql' );
		}

		$updater->addExtensionTable(
			'wb_changes_dispatch',
			$this->getScriptPath( 'wb_changes_dispatch', $type )
		);
	}

	private function updateItemsPerSiteTable( DatabaseUpdater $updater, IDatabase $db ) {
		if ( $db->getType() == 'postgres' ) {
			return;
		}
		// Make wb_items_per_site.ips_site_page VARCHAR(310) - T99459
		// NOTE: this update doesn't work on SQLite, but it's not needed there anyway.
		if ( $db->getType() !== 'sqlite' ) {
			$updater->modifyExtensionField(
				'wb_items_per_site',
				'ips_site_page',
				$this->getUpdateScriptPath( 'MakeIpsSitePageLarger', $db->getType() )
			);
		}

		// creates wb_item_per_site.ips_row_id.
		$updater->addExtensionField(
			'wb_items_per_site',
			'ips_row_id',
			$this->getUpdateScriptPath( 'AddRowIDs', $db->getType() )
		);

		$updater->dropExtensionIndex(
			'wb_items_per_site',
			'wb_ips_site_page',
			$this->getUpdateScriptPath( 'DropItemsPerSiteIndex', $db->getType() )
		);
	}

	private function updateChangesTable( DatabaseUpdater $updater, IDatabase $db ) {
		// Make wb_changes.change_info MEDIUMBLOB - T108246
		// NOTE: This update is neither needed nor does it work on SQLite or Postgres.
		if ( $db->getType() === 'mysql' ) {
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
			$file = $this->getScriptPath( $table, $type );

			$updater->addExtensionTable( $table, $file );

			// populate the table after creating it
			$updater->addExtensionUpdate( [
				[ __CLASS__, 'rebuildPropertyInfo' ]
			] );
		}
	}

	/**
	 * Wrapper for invoking PropertyInfoTableBuilder from DatabaseUpdater
	 * during a database update.
	 *
	 * @param DatabaseUpdater $updater
	 */
	public static function rebuildPropertyInfo( DatabaseUpdater $updater ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$localEntitySourceName = WikibaseRepo::getSettings()->getSetting( 'localEntitySourceName' );
		$propertySource = WikibaseRepo::getEntitySourceDefinitions()
			->getSourceForEntityType( 'property' );
		if ( $propertySource->getSourceName() !== $localEntitySourceName ) {
			// Foreign properties, skip this part
			return;
		}
		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback(
			function ( $msg ) use ( $updater ) {
				$updater->output( "..." . $msg . "\n" );
			}
		);

		$table = new PropertyInfoTable(
			WikibaseRepo::getEntityIdComposer(),
			$propertySource->getDatabaseName(),
			true
		);

		$contentCodec = $wikibaseRepo->getEntityContentDataCodec();
		$propertyInfoBuilder = $wikibaseRepo->newPropertyInfoBuilder();
		$entityNamespaceLookup = WikibaseRepo::getEntityNamespaceLookup();

		$wikiPageEntityLookup = new WikiPageEntityRevisionLookup(
			new WikiPageEntityMetaDataLookup(
				$entityNamespaceLookup,
				new EntityIdLocalPartPageTableEntityQuery(
					$entityNamespaceLookup,
					MediaWikiServices::getInstance()->getSlotRoleStore()
				),
				$propertySource,
				WikibaseRepo::getLogger()
			),
			new WikiPageEntityDataLoader( $contentCodec, MediaWikiServices::getInstance()->getBlobStore() ),
			MediaWikiServices::getInstance()->getRevisionStore(),
			false
		);

		$cachingEntityLookup = new CachingEntityRevisionLookup(
			new EntityRevisionCache( new HashBagOStuff() ),
			$wikiPageEntityLookup
		);
		$entityLookup = new RevisionBasedEntityLookup( $cachingEntityLookup );

		$builder = new PropertyInfoTableBuilder(
			$table,
			new LegacyAdapterPropertyLookup( $entityLookup ),
			$propertyInfoBuilder,
			$wikibaseRepo->getEntityIdComposer(),
			$entityNamespaceLookup
		);
		$builder->setReporter( $reporter );
		$builder->setUseTransactions( false );

		$updater->output( 'Populating ' . $table->getTableName() . "\n" );
		$builder->rebuildPropertyInfo();
	}

	public static function rebuildPropertyTerms( DatabaseUpdater $updater ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$localEntitySourceName = WikibaseRepo::getSettings()->getSetting( 'localEntitySourceName' );
		$propertySource = WikibaseRepo::getEntitySourceDefinitions()
			->getSourceForEntityType( 'property' );
		if ( $propertySource->getSourceName() !== $localEntitySourceName ) {
			// Foreign properties, skip this part
			return;
		}
		$sqlEntityIdPagerFactory = new SqlEntityIdPagerFactory(
			WikibaseRepo::getEntityNamespaceLookup(),
			WikibaseRepo::getEntityIdLookup()
		);
		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback(
			function ( $msg ) use ( $updater ) {
				$updater->output( "..." . $msg . "\n" );
			}
		);

		// Tables have potentially only just been created and we may need to wait, T268944
		$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
		$lbFactory->waitForReplication();

		$rebuilder = new PropertyTermsRebuilder(
			WikibaseRepo::getTermStoreWriterFactory()->newPropertyTermStoreWriter(),
			$sqlEntityIdPagerFactory->newSqlEntityIdPager( [ 'property' ] ),
			$reporter,
			$reporter,
			$lbFactory,
			$wikibaseRepo->getPropertyLookup( Store::LOOKUP_CACHING_RETRIEVE_ONLY ),
			250,
			2
		);

		$rebuilder->rebuild();
		$updater->insertUpdateRow( __CLASS__ . '::rebuildPropertyTerms' );
	}

	public static function rebuildItemTerms( DatabaseUpdater $updater ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$localEntitySourceName = WikibaseRepo::getSettings()->getSetting( 'localEntitySourceName' );
		$itemSource = WikibaseRepo::getEntitySourceDefinitions()
			->getSourceForEntityType( 'item' );
		if ( $itemSource->getSourceName() !== $localEntitySourceName ) {
			// Foreign items, skip this part
			return;
		}
		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback(
			function ( $msg ) use ( $updater ) {
				$updater->output( "..." . $msg . "\n" );
			}
		);

		$highestId = MediaWikiServices::getInstance()
			->getDBLoadBalancer()
			->getConnection( DB_REPLICA )
			->selectRow(
				'wb_id_counters',
				'id_value',
				[ 'id_type' => 'wikibase-item' ],
				__METHOD__
			);
		if ( $highestId === false ) {
			// Fresh instance, no need to rebuild anything
			return;
		}
		$highestId = (int)$highestId->id_value;

		// Tables have potentially only just been created and we may need to wait, T268944
		$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
		$lbFactory->waitForReplication();

		$rebuilder = new ItemTermsRebuilder(
			WikibaseRepo::getTermStoreWriterFactory()->newItemTermStoreWriter(),
			self::newItemIdIterator( $highestId ),
			$reporter,
			$reporter,
			$lbFactory,
			new LegacyAdapterItemLookup(
				WikibaseRepo::getStore()->getEntityLookup( Store::LOOKUP_CACHING_RETRIEVE_ONLY )
			),
			250,
			2
		);

		$rebuilder->rebuild();
		$updater->insertUpdateRow( __CLASS__ . '::rebuildItemTerms' );
	}

	private static function newItemIdIterator( int $highestId ): \Iterator {
		$idRange = new RangeTraversable(
			1,
			$highestId
		);

		foreach ( $idRange as $integer ) {
			yield ItemId::newFromNumber( $integer );
		}
	}

	private function getUpdateScriptPath( $name, $type ) {
		return $this->getScriptPath( 'archives/' . $name, $type );
	}

	private function getScriptPath( $name, $type ) {
		$types = [
			$type,
			'mysql'
		];

		foreach ( $types as $type ) {
			$path = __DIR__ . '/../../../sql/' . $type . '/' . $name . '.sql';

			if ( file_exists( $path ) ) {
				return $path;
			}
		}

		throw new MWException( "Could not find schema script '$name'" );
	}

	/**
	 * Static wrapper for EntityUsageTableBuilder::fillUsageTable
	 *
	 * @param DatabaseUpdater $dbUpdater
	 * @param string $table
	 */
	public static function fillSubscriptionTable( DatabaseUpdater $dbUpdater, $table ) {
		$primer = new ChangesSubscriptionTableBuilder(
			// would be nice to pass in $dbUpdater->getDB().
			MediaWikiServices::getInstance()->getDBLoadBalancer(),
			WikibaseRepo::getEntityIdComposer(),
			$table,
			1000
		);

		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback( function( $msg ) use ( $dbUpdater ) {
			$dbUpdater->output( "\t$msg\n" );
		} );
		$primer->setProgressReporter( $reporter );

		$primer->fillSubscriptionTable();
	}

}

<?php

namespace Wikibase\Client\Usage\Sql;

use DatabaseUpdater;
use MediaWiki\MediaWikiServices;
use MWException;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\Reporting\ObservableMessageReporter;

/**
 * Schema updater for SqlUsageTracker
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class SqlUsageTrackerSchemaUpdater {

	/**
	 * @var DatabaseUpdater
	 */
	private $dbUpdater;

	public function __construct( DatabaseUpdater $dbUpdater ) {
		$this->dbUpdater = $dbUpdater;
	}

	/**
	 * Static entry point for MediaWiki's LoadExtensionSchemaUpdates hook.
	 *
	 * @param DatabaseUpdater $dbUpdater
	 *
	 * @return bool
	 */
	public static function onSchemaUpdate( DatabaseUpdater $dbUpdater ) {
		$usageTrackerSchemaUpdater = new self( $dbUpdater );
		$usageTrackerSchemaUpdater->doSchemaUpdate();

		return true;
	}

	/**
	 * Applies any schema updates
	 */
	public function doSchemaUpdate() {
		$table = EntityUsageTable::DEFAULT_TABLE_NAME;
		$db = $this->dbUpdater->getDB();

		if ( !$this->dbUpdater->tableExists( $table ) ) {
			$script = $this->getUpdateScriptPath( 'entity_usage', $db->getType() );
			$this->dbUpdater->addExtensionTable( $table, $script );

			// Register function for populating the table.
			// Note that this must be done with a static function,
			// for reasons that do not need explaining at this juncture.
			$this->dbUpdater->addExtensionUpdate( [
				[ __CLASS__, 'fillUsageTable' ],
			] );
		} else {
			// This update is neither needed on SQLite nor does it work there.
			if ( $db->getType() !== 'sqlite' ) {
				$script = $this->getUpdateScriptPath( 'entity_usage-alter-aspect-varbinary-37', $db->getType() );
				$this->dbUpdater->modifyExtensionField( $table, 'eu_aspect', $script );
			}

			$script = $this->getUpdateScriptPath( 'entity_usage-drop-entity_type', $db->getType() );
			$this->dbUpdater->dropExtensionField( $table, 'eu_entity_type', $script );

			if ( $db->getType() === 'sqlite' ) {
				$script = $this->getUpdateScriptPath( 'entity_usage-drop-touched.sqlite', $db->getType() );
				$this->dbUpdater->dropExtensionField( $table, 'eu_touched', $script );
			} else {
				$script = $this->getUpdateScriptPath( 'entity_usage-drop-touched', $db->getType() );
				$this->dbUpdater->dropExtensionField( $table, 'eu_touched', $script );
			}
		}
	}

	/**
	 * Static wrapper for EntityUsageTableBuilder::fillUsageTable
	 *
	 * @param DatabaseUpdater $dbUpdater
	 */
	public static function fillUsageTable( DatabaseUpdater $dbUpdater ) {
		$idParser = WikibaseClient::getDefaultInstance()->getEntityIdParser();

		$primer = new EntityUsageTableBuilder(
			$idParser,
			// TODO: Would be nice to pass in $dbUpdater->getDB().
			MediaWikiServices::getInstance()->getDBLoadBalancer()
		);

		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback( function( $msg ) use ( $dbUpdater ) {
			$dbUpdater->output( "\t$msg\n" );
		} );
		$primer->setProgressReporter( $reporter );

		$primer->fillUsageTable();
	}

	private function getUpdateScriptPath( $name, $type ) {
		$extensions = [
			'.sql',
			'.' . $type . '.sql',
		];

		foreach ( $extensions as $ext ) {
			$path = __DIR__ . '/../../../sql/' . $name . $ext;

			if ( file_exists( $path ) ) {
				return $path;
			}
		}

		throw new MWException( "Could not find schema update script '$name'" );
	}

}

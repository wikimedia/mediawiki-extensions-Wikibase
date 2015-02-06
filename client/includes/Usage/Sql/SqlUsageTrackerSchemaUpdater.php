<?php

namespace Wikibase\Client\Usage\Sql;

use DatabaseUpdater;
use MWException;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\Reporting\ObservableMessageReporter;

/**
 * Schema updater for SqlUsageTracker
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class SqlUsageTrackerSchemaUpdater {

	/**
	 * @var DatabaseUpdater
	 */
	private $dbUpdater;

	/**
	 * @param DatabaseUpdater $dbUpdater
	 */
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
		if ( WikibaseClient::getDefaultInstance()->getSettings()->getSetting( 'useLegacyUsageIndex' ) ) {
			return true;
		}

		$usageTrackerSchemaUpdater = new self( $dbUpdater );
		$usageTrackerSchemaUpdater->doSchemaUpdate();

		return true;
	}

	/**
	 * Applies any schema updates
	 */
	public function doSchemaUpdate() {
		$table = 'wbc_entity_usage';

		if ( !$this->dbUpdater->tableExists( $table ) ) {
			$db = $this->dbUpdater->getDB();
			$script = $this->getUpdateScriptPath( 'entity_usage', $db->getType() );
			$this->dbUpdater->addExtensionTable( $table, $script );

			// Register function for populating the table.
			// Note that this must be done with a static function,
			// for reasons that do not need explaining at this juncture.
			$this->dbUpdater->addExtensionUpdate( array(
				array( __CLASS__, 'fillUsageTable' ),
				$table
			) );
		}
	}

	/**
	 * Static wrapper for EntityUsageTableBuilder::fillUsageTable
	 *
	 * @param DatabaseUpdater $dbUpdater
	 * @param string $table
	 */
	public static function fillUsageTable( DatabaseUpdater $dbUpdater, $table ) {
		$idParser = WikibaseClient::getDefaultInstance()->getEntityIdParser();

		$primer = new EntityUsageTableBuilder(
			$idParser,
			wfGetLB(), // would be nice to pass in $dbUpdater->getDB().
			$table,
			1000
		);

		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback( function( $msg ) use ( $dbUpdater ) {
			$dbUpdater->output( "\t$msg\n" );
		} );
		$primer->setProgressReporter( $reporter );

		$primer->fillUsageTable();
	}

	private function getUpdateScriptPath( $name, $type ) {
		$extensions = array(
			'.sql',
			'.' . $type . '.sql',
		);

		foreach ( $extensions as $ext ) {
			$path = __DIR__ . '/../../../sql/' . $name . $ext;

			if ( file_exists( $path ) ) {
				return $path;
			}
		}

		throw new MWException( "Could not find schema update script '$name'" );
	}

}

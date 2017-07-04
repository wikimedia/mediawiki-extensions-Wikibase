<?php

namespace Wikibase\Repo\Store\Sql;

use DatabaseUpdater;
use MWException;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikibase\Repo\WikibaseRepo;

/**
 * Schema updater for the wb_changes_subscription table.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class ChangesSubscriptionSchemaUpdater {

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
		$changesSubscriptionSchemaUpdater = new self( $dbUpdater );
		$changesSubscriptionSchemaUpdater->doSchemaUpdate();

		return true;
	}

	/**
	 * Applies any schema updates
	 */
	public function doSchemaUpdate() {
		$table = 'wb_changes_subscription';

		if ( !$this->dbUpdater->tableExists( $table ) ) {
			$db = $this->dbUpdater->getDB();
			$script = $this->getUpdateScriptPath( 'changes_subscription', $db->getType() );
			$this->dbUpdater->addExtensionTable( $table, $script );

			// Register function for populating the table.
			// Note that this must be done with a static function,
			// for reasons that do not need explaining at this juncture.
			$this->dbUpdater->addExtensionUpdate( [
				[ __CLASS__, 'fillSubscriptionTable' ],
				$table
			] );
		}
	}

	/**
	 * Static wrapper for EntityUsageTableBuilder::fillUsageTable
	 *
	 * @param DatabaseUpdater $dbUpdater
	 * @param string $table
	 */
	public static function fillSubscriptionTable( DatabaseUpdater $dbUpdater, $table ) {
		$primer = new ChangesSubscriptionTableBuilder(
			wfGetLB(), // would be nice to pass in $dbUpdater->getDB().
			WikibaseRepo::getDefaultInstance()->getEntityIdComposer(),
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

	/**
	 * @param string $name
	 * @param string $type
	 *
	 * @throws MWException
	 * @return string
	 */
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

		throw new MWException( "Could not find schema update script '$name'." );
	}

}

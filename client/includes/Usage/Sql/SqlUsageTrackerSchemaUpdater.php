<?php

namespace Wikibase\Client\Usage\Sql;

use DatabaseUpdater;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;
use MediaWiki\MediaWikiServices;
use MWException;
use Onoi\MessageReporter\CallbackMessageReporter;
use Wikibase\Client\WikibaseClient;

/**
 * Schema updater for SqlUsageTracker
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class SqlUsageTrackerSchemaUpdater implements LoadExtensionSchemaUpdatesHook {
	/**
	 * Applies any schema updates
	 *
	 * @param DatabaseUpdater $updater DatabaseUpdater subclass
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$table = EntityUsageTable::DEFAULT_TABLE_NAME;
		$db = $updater->getDB();

		if ( !$updater->tableExists( $table ) ) {
			$script = $this->getScriptPath( 'entity_usage', $db->getType() );
			$updater->addExtensionTable( $table, $script );

			// Register function for populating the table.
			// Note that this must be done with a static function,
			// for reasons that do not need explaining at this juncture.
			$updater->addExtensionUpdate( [
				[ __CLASS__, 'fillUsageTable' ],
			] );
		} else {
			// This update is neither needed nor does it work on SQLite or Postgres.
			if ( $db->getType() === 'mysql' ) {
				$script = $this->getUpdateScriptPath( 'entity_usage-alter-aspect-varbinary-37', $db->getType() );
				$updater->modifyExtensionField( $table, 'eu_aspect', $script );
			}

			$script = $this->getUpdateScriptPath( 'entity_usage-drop-entity_type', $db->getType() );
			$updater->dropExtensionField( $table, 'eu_entity_type', $script );

			$script = $this->getUpdateScriptPath( 'entity_usage-drop-touched', $db->getType() );
			$updater->dropExtensionField( $table, 'eu_touched', $script );
		}
	}

	/**
	 * Static wrapper for EntityUsageTableBuilder::fillUsageTable
	 *
	 * @param DatabaseUpdater $dbUpdater
	 */
	public static function fillUsageTable( DatabaseUpdater $dbUpdater ) {
		$idParser = WikibaseClient::getEntityIdParser();

		$primer = new EntityUsageTableBuilder(
			$idParser,
			// TODO: Would be nice to pass in $dbUpdater->getDB().
			MediaWikiServices::getInstance()->getDBLoadBalancerFactory()
		);

		$primer->setProgressReporter(
			new CallbackMessageReporter(
				function( $msg ) use ( $dbUpdater ) {
					$dbUpdater->output( "\t$msg\n" );
				}
			)
		);

		$primer->fillUsageTable();
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

}

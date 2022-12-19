<?php

declare( strict_types=1 );

namespace Wikibase\Client\Usage\Sql;

use DatabaseUpdater;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;
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
	public function onLoadExtensionSchemaUpdates( $updater ): void {
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
			$script = $this->getUpdateScriptPath( 'entity_usage-drop-touched', $db->getType() );
			$updater->dropExtensionField( $table, 'eu_touched', $script );
		}
	}

	/**
	 * Static wrapper for EntityUsageTableBuilder::fillUsageTable
	 */
	public static function fillUsageTable( DatabaseUpdater $dbUpdater ): void {
		$idParser = WikibaseClient::getEntityIdParser();

		$primer = new EntityUsageTableBuilder(
			$idParser,
			// TODO: Would be nice to pass in $dbUpdater->getDB().
			WikibaseClient::getClientDomainDbFactory()->newLocalDb()
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

	private function getUpdateScriptPath( string $name, string $type ): string {
		return $this->getScriptPath( 'archives/' . $name, $type );
	}

	private function getScriptPath( string $name, string $type ): string {
		$types = [
			$type,
			'mysql',
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

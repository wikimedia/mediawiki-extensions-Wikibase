<?php

declare( strict_types=1 );

namespace Wikibase\Client\Usage\Sql;

use MediaWiki\Installer\DatabaseUpdater;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;
use Onoi\MessageReporter\CallbackMessageReporter;
use RuntimeException;
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
		$dbType = $updater->getDB()->getType();

		$updater->addExtensionUpdateOnVirtualDomain( [
			EntityUsageDomainDb::VIRTUAL_DOMAIN_ID,
			'addTable',
			EntityUsageTable::DEFAULT_TABLE_NAME,
			$this->getScriptPath( 'entity_usage', $dbType ),
			true,
		] );

		// Register function for populating the table.
		// TODO: Should this be guarded behind updateRowExists?
		$updater->addExtensionUpdate( [
			[ __CLASS__, 'fillUsageTable' ],
		] );

		$updater->addExtensionUpdateOnVirtualDomain( [
			EntityUsageDomainDb::VIRTUAL_DOMAIN_ID,
			'dropField',
			EntityUsageTable::DEFAULT_TABLE_NAME,
			'eu_touched',
			$this->getUpdateScriptPath( 'entity_usage-drop-touched', $dbType ),
			true,
		] );
	}

	/**
	 * Static wrapper for EntityUsageTableBuilder::fillUsageTable
	 */
	public static function fillUsageTable( DatabaseUpdater $dbUpdater ): void {
		$idParser = WikibaseClient::getEntityIdParser();

		$primer = new EntityUsageTableBuilder(
			$idParser,
			// TODO: Would be nice to pass in $dbUpdater->getDB().
			WikibaseClient::getClientDomainDbFactory()->newLocalDb(),
			WikibaseClient::getEntityUsageDomainDb()
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

		throw new RuntimeException( "Could not find schema script '$name'" );
	}

}

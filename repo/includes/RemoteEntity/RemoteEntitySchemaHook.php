<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\RemoteEntity;

use MediaWiki\Installer\DatabaseUpdater;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

/**
 * Registers the wb_remote_entity table for federation remote entity caching.
 *
 * This hook is invoked by maintenance/run.php update.php.
 *
 * @license GPL-2.0-or-later
 */
class RemoteEntitySchemaHook implements LoadExtensionSchemaUpdatesHook {

	/**
	 * @param DatabaseUpdater $updater
	 */
	public function onLoadExtensionSchemaUpdates( $updater ): void {
		// __DIR__ = .../extensions/Wikibase/repo/includes/RemoteEntity
		// We want .../extensions/Wikibase/repo/sql
		$sqlBaseDir = dirname( __DIR__, 2 ) . '/sql';
		$dbType = $updater->getDB()->getType(); // 'mysql', 'postgres', 'sqlite', ...

		switch ( $dbType ) {
			case 'mysql':
				$updater->addExtensionTable(
					'wb_remote_entity',
					$sqlBaseDir . '/mysql/patch-add-wb_remote_entity.sql'
				);
				break;

			case 'postgres':
				$updater->addExtensionTable(
					'wb_remote_entity',
					$sqlBaseDir . '/postgres/patch-add-wb_remote_entity.sql'
				);
				break;

			case 'sqlite':
				$updater->addExtensionTable(
					'wb_remote_entity',
					$sqlBaseDir . '/sqlite/patch-add-wb_remote_entity.sql'
				);
				break;

			default:
				// Other DBs are not supported by Wikibase anyway,
				// so just do nothing.
				break;
		}
	}
}

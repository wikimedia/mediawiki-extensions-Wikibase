<?php

namespace Wikibase\Repo\Store\Sql;

use DatabaseUpdater;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;
use MediaWiki\MediaWikiServices;
use MWException;
use Onoi\MessageReporter\ObservableMessageReporter;
use Wikibase\Repo\WikibaseRepo;

/**
 * Schema updater for the wb_changes_subscription table.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ChangesSubscriptionSchemaUpdater implements LoadExtensionSchemaUpdatesHook {
	/**
	 * Handler for MediaWiki's LoadExtensionSchemaUpdates hook.
	 *
	 * @param DatabaseUpdater $dbUpdater
	 */
	public function onLoadExtensionSchemaUpdates( $dbUpdater ): void {
		$table = 'wb_changes_subscription';

		if ( !$dbUpdater->tableExists( $table ) ) {
			$db = $dbUpdater->getDB();
			$script = $this->getUpdateScriptPath( 'changes_subscription', $db->getType() );
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
	 * Static wrapper for EntityUsageTableBuilder::fillUsageTable
	 *
	 * @param DatabaseUpdater $dbUpdater
	 * @param string $table
	 */
	public static function fillSubscriptionTable( DatabaseUpdater $dbUpdater, $table ) {
		$primer = new ChangesSubscriptionTableBuilder(
			// would be nice to pass in $dbUpdater->getDB().
			MediaWikiServices::getInstance()->getDBLoadBalancer(),
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

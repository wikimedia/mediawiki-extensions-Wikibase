<?php

declare( strict_types=1 );

namespace Wikibase\Client\Hooks;

use DatabaseUpdater;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;
use Onoi\MessageReporter\CallbackMessageReporter;
use Wikibase\Client\Store\Sql\UnexpectedUnconnectedPagePrimer;
use Wikibase\Client\WikibaseClient;

/**
 * Handler for the "LoadExtensionSchemaUpdates" hook.
 *
 * This can't use dependency injection as this hook is used in a context where
 * the global service locator is not yet initialised.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch <mail@mariushoch.de>
 */
class LoadExtensionSchemaUpdatesHookHandler implements LoadExtensionSchemaUpdatesHook {

	public const UPDATE_KEY_UNEXPECTED_UNCONNECTED_PAGE = 'Wikibase-Client-primeUnexpectedUnconnectedPage-v3';

	/**
	 * Applies any schema updates
	 *
	 * @param DatabaseUpdater $updater DatabaseUpdater subclass
	 */
	public function onLoadExtensionSchemaUpdates( $updater ): void {
		if ( !$updater->updateRowExists( self::UPDATE_KEY_UNEXPECTED_UNCONNECTED_PAGE ) ) {
			$updater->addExtensionUpdate( [
				[ __CLASS__, 'primeUnexpectedUnconnectedPage' ],
			] );
		}
	}

	/**
	 * Static wrapper for UnexpectedUnconnectedPagePrimer::insertPageProp
	 */
	public static function primeUnexpectedUnconnectedPage( DatabaseUpdater $dbUpdater ): void {
		$primer = new UnexpectedUnconnectedPagePrimer(
			WikibaseClient::getClientDomainDbFactory()->newLocalDb(),
			WikibaseClient::getNamespaceChecker()
		);

		$primer->setProgressReporter(
			new CallbackMessageReporter(
				function( $msg ) use ( $dbUpdater ) {
					$dbUpdater->output( "\t$msg\n" );
				}
			)
		);

		$primer->setPageProps();
		$dbUpdater->insertUpdateRow( self::UPDATE_KEY_UNEXPECTED_UNCONNECTED_PAGE );
	}

}

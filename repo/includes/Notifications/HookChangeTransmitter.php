<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Notifications;

use Wikibase\Lib\Changes\Change;
use Wikibase\Repo\Hooks\WikibaseChangeNotificationHook;

/**
 * Change notification channel using a MediaWiki hook container.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class HookChangeTransmitter implements ChangeTransmitter {

	private WikibaseChangeNotificationHook $hookRunner;

	public function __construct( WikibaseChangeNotificationHook $hookRunner ) {
		$this->hookRunner = $hookRunner;
	}

	public function transmitChange( Change $change ): void {
		$this->hookRunner->onWikibaseChangeNotification( $change );
	}

}

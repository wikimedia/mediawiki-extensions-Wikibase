<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Hooks;

use MediaWiki\Extension\Notifications\Hooks\EchoGetBundleRulesHook;
use MediaWiki\Extension\Notifications\Model\Event;

/**
 * @license GPL-2.0-or-later
 */
class EchoGetBundleRulesHandler implements EchoGetBundleRulesHook {

	/**
	 * @inheritDoc
	 */
	public function onEchoGetBundleRules( Event $event, &$bundleKey ) {
		if ( $event->getType() === EchoNotificationsHandlers::NOTIFICATION_TYPE ) {
			$bundleKey = EchoNotificationsHandlers::NOTIFICATION_TYPE;
		}
	}

}

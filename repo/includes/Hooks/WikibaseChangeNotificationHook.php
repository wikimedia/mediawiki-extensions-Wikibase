<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

use Wikibase\Lib\Changes\Change;

/**
 * This is a hook handler interface, see docs/Hooks.md in MediaWiki core.
 * Use the hook name "WikibaseChangeNotification" to register
 * handlers implementing this interface.
 *
 * @license GPL-2.0-or-later
 */
interface WikibaseChangeNotificationHook {

	/**
	 * Triggered from ChangeNotifier via a {@link \Wikibase\Repo\Notifications\HookChangeTransmitter HookChangeTransmitter}
	 * to notify any listeners of changes to entities.
	 *
	 * For performance reasons, does not include statement, description and alias diffs
	 * (see {@link https://phabricator.wikimedia.org/T113468 T113468},
	 * {@link https://phabricator.wikimedia.org/T163465 T163465}).
	 *
	 * @param Change $change The Change object representing the change.
	 */
	public function onWikibaseChangeNotification( Change $change ): void;

}

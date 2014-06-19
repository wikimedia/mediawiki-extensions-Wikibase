<?php

namespace Wikibase\Repo\Notifications;

use Wikibase\Change;

/**
 * Dummy notification channel. All notifications are ignored.
 *
 * @since 0.5
 *
 * @author Daniel Kinzler
 */
class DummyChangeNotificationChannel implements ChangeNotificationChannel {

	/**
	 * @see ChangeNotificationChannel::sendChangeNotification()
	 *
	 * This dummy implementation does nothing.
	 *
	 * @param Change $change
	 */
	public function sendChangeNotification( Change $change ) {
		// noop
	}

}
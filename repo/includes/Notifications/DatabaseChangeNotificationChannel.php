<?php

namespace Wikibase\Repo\Notifications;

use Wikibase\Change;
use Wikibase\ChangeRow;

/**
 * Notification channel based on a database table.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DatabaseChangeNotificationChannel implements ChangeNotificationChannel {

	/**
	 * @see ChangeNotificationChannel::sendChangeNotification()
	 *
	 * Saves the change to a database table.
	 *
	 * @note Only supports Change objects that are derived from ChangeRow.
	 *
	 * @param Change $change
	 *
	 * @throws ChangeNotificationException
	 */
	public function sendChangeNotification( Change $change ) {

		//XXX: the Change interface does not define save().
		/* @var ChangeRow $change */
		$ok = $change->save();

		if ( !$ok ) {
			throw new ChangeNotificationException( 'Failed to record change to the database' );
		}
	}

}
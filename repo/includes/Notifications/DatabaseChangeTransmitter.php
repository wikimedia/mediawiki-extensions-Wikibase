<?php

namespace Wikibase\Repo\Notifications;

use Wikibase\Change;
use Wikibase\Repo\Store\ChangeStore;

/**
 * Notification channel based on a database table.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class DatabaseChangeTransmitter implements ChangeTransmitter {

	/**
	 * @var ChangeStore
	 */
	private $changeStore;

	public function __construct( ChangeStore $changeStore ) {
		$this->changeStore = $changeStore;
	}

	/**
	 * @see ChangeNotificationChannel::sendChangeNotification()
	 *
	 * Saves the change to a database table.
	 *
	 * @note Only supports Change objects that are derived from ChangeRow.
	 *
	 * @param Change $change
	 */
	public function transmitChange( Change $change ) {
		$this->changeStore->saveChange( $change );
	}

}

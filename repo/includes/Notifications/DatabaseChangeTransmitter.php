<?php

namespace Wikibase\Repo\Notifications;

use Wikibase\Change;
use Wikibase\Repo\Store\ChangeStore;

/**
 * Notification channel based on a database table.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch
 */
class DatabaseChangeTransmitter implements ChangeTransmitter {

	/**
	 * @var ChangeStore
	 */
	private $changeStore;

	/**
	 * @param ChangeStore $changeStore
	 */
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

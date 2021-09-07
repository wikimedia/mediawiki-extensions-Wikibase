<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Notifications;

use Wikibase\Lib\Changes\Change;

/**
 * Notification channel based on a database table.
 *
 * @license GPL-2.0-or-later
 */
class ChangeHolder implements ChangeTransmitter {

	/**
	 * @var Change[]
	 */
	private $changes;

	public function __construct() {
		$this->changes = [];
	}

	/**
	 * @see ChangeNotificationChannel::sendChangeNotification()
	 * Holds the change to be stored later.
	 *
	 * @param Change $change
	 */
	public function transmitChange( Change $change ) {
		$this->changes[] = $change;
	}

	public function getChanges(): array {
		return $this->changes;
	}

}

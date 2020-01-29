<?php

namespace Wikibase\Repo\Notifications;

use Wikibase\Lib\Changes\Change;

/**
 * Channel for sending notifications about changes on the repo to any clients.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
interface ChangeTransmitter {

	/**
	 * Sends the given change over the channel.
	 *
	 * @param Change $change
	 *
	 * @throws ChangeTransmitterException
	 */
	public function transmitChange( Change $change );

}

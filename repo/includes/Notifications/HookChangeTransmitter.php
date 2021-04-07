<?php

namespace Wikibase\Repo\Notifications;

use MediaWiki\HookContainer\HookContainer;
use Wikibase\Lib\Changes\Change;

/**
 * Change notification channel using a MediaWiki hook container.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class HookChangeTransmitter implements ChangeTransmitter {

	/** @var HookContainer */
	private $hookContainer;

	/**
	 * @var string
	 */
	private $hookName;

	public function __construct( HookContainer $hookContainer, string $hookName ) {
		$this->hookContainer = $hookContainer;
		$this->hookName = $hookName;
	}

	/**
	 * @see ChangeNotificationChannel::sendChangeNotification()
	 *
	 * @param Change $change
	 */
	public function transmitChange( Change $change ) {
		$this->hookContainer->run( $this->hookName, [ $change ] );
	}

}

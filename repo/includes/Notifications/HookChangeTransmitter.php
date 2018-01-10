<?php

namespace Wikibase\Repo\Notifications;

use Hooks;
use Wikibase\Change;
use Wikimedia\Assert\Assert;

/**
 * Change notification channel using MediaWiki's global scope Hook facility.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class HookChangeTransmitter implements ChangeTransmitter {

	/**
	 * @var string
	 */
	private $hookName;

	/**
	 * @param string $hookName
	 */
	public function __construct( $hookName ) {
		Assert::parameterType( 'string', $hookName, '$hookName' );

		$this->hookName = $hookName;
	}

	/**
	 * @see ChangeNotificationChannel::sendChangeNotification()
	 *
	 * @param Change $change
	 */
	public function transmitChange( Change $change ) {
		Hooks::run( $this->hookName, [ $change ] );
	}

}

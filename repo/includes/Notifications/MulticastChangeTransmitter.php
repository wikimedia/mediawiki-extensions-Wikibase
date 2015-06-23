<?php

namespace Wikibase\Repo\Notifications;

use Wikibase\Change;
use Wikimedia\Assert\Assert;

/**
 * Multicast notification channel, implemented to forward each change to all
 * registered ChangeTransmitters.
 *
 * @since 0.5
 *
 * @author Daniel Kinzler
 */
class MulticastChangeTransmitter implements ChangeTransmitter {

	/**
	 * @var ChangeTransmitter[]
	 */
	private $transmitters;

	/**
	 * @param ChangeTransmitter[] $transmitters
	 */
	public function __construct( array $transmitters ) {
		Assert::parameterElementType( 'Wikibase\Repo\Notifications\ChangeTransmitter', $transmitters, '$transmitters' );

		$this->transmitters = $transmitters;
	}

	/**
	 * @see ChangeNotificationChannel::sendChangeNotification()
	 *
	 * This dummy implementation does nothing.
	 *
	 * @param Change $change
	 */
	public function transmitChange( Change $change ) {
		foreach ( $this->transmitters as $transmitter ) {
			$transmitter->transmitChange( $change );
		}
	}

}
<?php

namespace Wikibase;

/**
 * Helper class to handle subscriptions in Subscribable implementing classes.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SubscribtionHandler implements Subscribable {

	/**
	 * @since 0.1
	 *
	 * @var array of callable
	 */
	protected $subscribers;

	/**
	 * Subscribes the provided function to changes.
	 *
	 * @since 0.1
	 *
	 * @param callable $function
	 */
	public function subscribe( /* callable */ $function ) {
		// Manual check, since we're still caring about PHP 5.3.x.
		if ( !is_callable( $function ) ) {
			throw new \MWException( 'Non callable argument cannot be registered as subscriber' );
		}

		if ( !in_array( $function, $this->subscribers, true ) ) {
			$this->subscribers[] = $function;
		}
	}

	/**
	 * Unsubscribes the provided function from changes.
	 *
	 * @since 0.1
	 *
	 * @param callable $function
	 */
	public function unsubscribe( /* callable */ $function ) {
		// Manual check, since we're still caring about PHP 5.3.x.
		if ( !is_callable( $function ) ) {
			throw new \MWException( 'Non callable argument cannot be unregistered as subscriber' );
		}

		$position = array_search( $function, $this->subscribers, true );

		if ( $position !== false ) {
			unset( $this->subscribers[$position] );
		}
	}

	/**
	 * Notifies all subscribers.
	 *
	 * @since 0.1
	 */
	public function notifySubscribers() {
		foreach ( $this->subscribers as $subscriber ) {
			call_user_func( $subscriber, $this );
		}
	}

}
this.util = this.util || {};

util.Notifier = ( function() {
	'use strict';

	/**
	 * Tracks the current notifications for the `Notifier`'s current function.
	 *
	 * @property {string[]}
	 * @ignore
	 */
	var currentNotifications = [];

	/**
	 * Constructor to create an object which takes several callbacks in its constructor. Each
	 * callback is mapped to a keyword. The keyword can be used in a `notify` function which will
	 * then trigger the callback. The notification object itself is immutable. The object will only
	 * hold a reference to the given map though, and won't copy the map. So, if the map changes from
	 * the outside, the notifier will also be affected.
	 * Instantiation also works without using the `new` keyword.
	 *
	 *     @example
	 *     var notifier = util.Notifier( {
	 *         valid: function() { this.current() },
	 *         invalid: function() { this.current() }
	 *     } );
	 *     notifier.notify( 'valid' ); // will alert 'valid'
	 *     notifier.notify( 'invalid' ); // will alert 'invalid'
	 *     notifier.notify( 'whatever' ); // Nothing happens, no notification registered for this one.
	 *
	 * @class util.Notifier
	 * @license GNU GPL v2+
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 *
	 * @constructor
	 *
	 * @param {Object} [notificationMap={}] Map from notification IDs to callback functions. The
	 *        context of the functions when called by `notify()` is the `Notifier` instance.
	 *
	 * @throws {Error} if notification map is not specified properly.
	 */
	var SELF = function Notifier( notificationMap ) {
		// allow instance without "new":
		if ( !( this instanceof SELF ) ) {
			return new SELF( notificationMap );
		}

		if ( !notificationMap ) {
			notificationMap = {};
		} else if ( typeof notificationMap !== 'object' ) {
			throw new Error( 'Notifier requires a notification map in form of an object' );
		}

		/**
		 * Will trigger a callback related to a given notification string if there is a callback
		 * function defined for that string.
		 *
		 * @param {string} notification
		 * @param {Array} [args=[]] Optional arguments that will be provided to the callback.
		 * @return {boolean} Whether a notification has been sent. false if the notification has not
		 *         been registered in the constructor.
		 */
		this.notify = function( notification, args ) {
			var notifyCallback = notificationMap[ notification ];

			if ( !notifyCallback ) {
				return false;
			}

			// for this.current() to keep track over current notifications
			currentNotifications.push( notification );

			// context of the callback will be the Notifier instance
			notifyCallback.apply( this, args || [] );

			// NOTE: the above might fail with an error. If there is a try outside the notify() call,
			//  the currentNotifications won't be updated after the error got triggered. Putting
			//  the above in a try would be annoying for debugging. currentNotifications having
			//  remnants doesn't have any side-effects as long as we don't use it as an implication
			//  for whether we are inside of a notification right now.

			currentNotifications.pop();
			return true;
		};

		/**
		 * Returns what is currently being notified. Will only return a value when used within a
		 * callback because only within callbacks things are being notified.
		 *
		 * @return {string|null}
		 */
		this.current = function() {
			var current = currentNotifications[ currentNotifications.length - 1 ];
			return current !== undefined ? current : null;
		};
	};

	return SELF;

}() );

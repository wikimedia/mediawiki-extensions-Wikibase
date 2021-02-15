/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function () {
	'use strict';

	/**
	 * Manages attaching an event handler to a target only once for a set of source objects.
	 * Since an event is attached only once, the initial event handler (for the combination of target/
	 * event name/event namespace) may not be overwritten later on.
	 *
	 * @constructor
	 */
	var SELF = function UtilEventSingletonManager() {
		this._registry = [];
	};

	$.extend( SELF.prototype, {
		/**
		 * @type {Object[]}
		 */
		_registry: [],

		/**
		 * Attaches an event handler to a target element unless it is not attached already. The event
		 * binding is registered for a source element.
		 *
		 * @param {*} source
		 * @param {HTMLElement|Object} target
		 * @param {string} event
		 * @param {Function} handler
		 *        Will receive the following arguments when the event is triggered:
		 *        - {jQuery.Event} Object representing the actual event triggered on the target.
		 *        - {*} source
		 * @param {Object} [options]
		 *        - {number} [throttle] Throttle delay
		 *          Default: undefined (no throttling)
		 *        - {number} [debounce] Debounce delay
		 *          Default: undefined (no debouncing)
		 */
		register: function ( source, target, event, handler, options ) {
			var namespacedEvents = event.split( ' ' );

			options = options || {};

			for ( var i = 0; i < namespacedEvents.length; i++ ) {
				var registration = this._getRegistration( target, namespacedEvents[ i ] );

				if ( registration ) {
					registration.sources.push( source );
				} else {
					this._attach( source, target, namespacedEvents[ i ], handler, options );
				}
			}
		},

		/**
		 * Unregisters one or multiple events attached to a target element and registered for a specific
		 * source.
		 *
		 * @param {*} source
		 * @param {HTMLElement|Object} target
		 * @param {string} event
		 *        Instead of white-space separated list of event names, a single namespace may be passed
		 *        to remove all events attached to target and registered on source.
		 */
		unregister: function ( source, target, event ) {
			var registrations = [],
				i;

			if ( event.indexOf( '.' ) === 0 ) {
				registrations = this._getRegistrations( target, event.split( '.' )[ 1 ] );
			} else {
				var events = event.split( ' ' );
				for ( i = 0; i < events.length; i++ ) {
					var registration = this._getRegistration( target, events[ i ] );
					if ( registration ) {
						registrations.push( registration );
					}
				}
			}

			for ( i = 0; i < registrations.length; i++ ) {
				var index = registrations[ i ].sources.indexOf( source );
				if ( index !== -1 ) {
					registrations[ i ].sources.splice( index, 1 );
				}
				if ( !registrations[ i ].sources.length ) {
					this._detach( registrations[ i ] );
				}
			}
		},

		/**
		 * @param {HTMLElement|Object} target
		 * @param {string} event
		 * @return {Object}
		 */
		_getRegistration: function ( target, event ) {
			var eventSegments = event.split( '.' );

			for ( var i = 0; i < this._registry.length; i++ ) {
				if ( this._registry[ i ].target === target
					&& this._registry[ i ].event === eventSegments[ 0 ]
					&& this._registry[ i ].namespace === eventSegments[ 1 ]
				) {
					return this._registry[ i ];
				}
			}
		},

		/**
		 * @param {HTMLElement|Object} target
		 * @param {string} namespace
		 * @return {Object[]}
		 */
		_getRegistrations: function ( target, namespace ) {
			var registered = [];

			for ( var i = 0; i < this._registry.length; i++ ) {
				if ( this._registry[ i ].target === target && this._registry[ i ].namespace === namespace ) {
					registered.push( this._registry[ i ] );
				}
			}

			return registered;
		},

		/**
		 * @param {*} source
		 * @param {HTMLElement|Object} target
		 * @param {string} event
		 * @param {Function} handler
		 * @param {Object} options
		 */
		_attach: function ( source, target, event, handler, options ) {
			var self = this,
				eventSegments = event.split( '.' ),
				actualHandler = function ( actualEvent ) {
					self._triggerHandler( target, event, actualEvent );
				},
				alteredHandler;

			if ( options.throttle ) {
				alteredHandler = OO.ui.throttle( actualHandler, options.throttle );
			} else if ( options.debounce ) {
				alteredHandler = OO.ui.debounce( actualHandler, options.debounce );
			}

			$( target ).on( event, alteredHandler || actualHandler );

			this._registry.push( {
				sources: [ source ],
				target: target,
				event: eventSegments[ 0 ],
				namespace: eventSegments[ 1 ],
				handler: handler
			} );
		},

		/**
		 * @param {Object} registration
		 */
		_detach: function ( registration ) {
			var namespaced = registration.event;
			if ( registration.namespace ) {
				namespaced += '.' + registration.namespace;
			}
			$( registration.target ).off( namespaced );

			for ( var i = 0; i < this._registry.length; i++ ) {
				if ( this._registry[ i ].target === registration.target
					&& this._registry[ i ].event === registration.event
					&& this._registry[ i ].namespace === registration.namespace
				) {
					this._registry.splice( i, 1 );
				}
			}
		},

		/**
		 * @param {HTMLElement|Object} target
		 * @param {string} event
		 * @param {jQuery.Event} actualEvent
		 */
		_triggerHandler: function ( target, event, actualEvent ) {
			var registration = this._getRegistration( target, event );

			if ( registration ) {
				for ( var i = 0; i < registration.sources.length; i++ ) {
					registration.handler( actualEvent, registration.sources[ i ] );
				}
			}
		}
	} );

	module.exports = SELF;

}() );

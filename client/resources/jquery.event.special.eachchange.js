( function () {
	'use strict';

	/**
	 * Event id used for data binding and as namespace.
	 *
	 * @property {string}
	 * @ignore
	 */
	var EVENT_ID = 'jqueryEventSpecialEachchange';

	var triggeredHandlers = [];

	/**
	 * Checks whether a handler with a given event id has already been triggered.
	 *
	 * @ignore
	 *
	 * @param {string} eventId
	 * @param {number} index Numeric index within the list of handlers attached with the same
	 *        event id.
	 */
	function alreadyTriggered( eventId, index ) {
		for ( var i = 0; i < triggeredHandlers.length; i++ ) {
			if ( eventId === triggeredHandlers[ i ].id && index === triggeredHandlers[ i ].index ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns the value of a jQuery element or null if the element does not feature retrieving its
	 * value via .val().
	 *
	 * @ignore
	 *
	 * @param {jQuery} $elem
	 * @return {*}
	 */
	function getValue( $elem ) {
		// If the native element does not feature getting its value, an error is caused in the
		// jQuery mechanism trying to retrieve the value.
		try {
			return $elem.val();
		} catch ( e ) {
			return null;
		}
	}

	/**
	 * Assigns a namespace to a string of one or more event names separated by a space character.
	 *
	 * @ignore
	 *
	 * @param {string} eventNames
	 * @param {string} namespace
	 * @return {string}
	 */
	function assignNamespace( eventNames, namespace ) {
		var names = eventNames.split( ' ' ),
			namespacedNames = [];

		for ( var i = 0; i < names.length; i++ ) {
			namespacedNames.push( names[ i ] + '.' + namespace );
		}

		return namespacedNames.join( ' ' );
	}

	/**
	 * eachchange jQuery event
	 *
	 * The `eachchange` event catches all designated input events. In recent browsers, it basically
	 * delegates to the `input` event. Older browsers are supported by fallback events to achieve
	 * some kind of simulation of the `input` event.
	 *
	 *     @example
	 *     $( 'input' ).on( 'eachchange', function( event, previousValue ) {
	 *         console.log( 'previous value: ' + previousValue );
	 *         console.log( 'new value: ' + $( event.target ).val() );
	 *     } );
	 *
	 * @see jQuery.event.special
	 *
	 * @class jQuery.event.special.eachchange
	 * @extends jQuery.Event
	 * @license GNU GPL v2+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @param {jQuery.Event} event
	 * @param {string} previousValue
	 */
	$.event.special.eachchange = {
		add: function ( handleObj ) {
			var eventData = $.data( this, EVENT_ID + handleObj.namespace ),
				$elem = $( this ),
				eventId = EVENT_ID + handleObj.namespace,
				eventNameString = assignNamespace( 'input', eventId );

			if ( !eventData ) {
				eventData = { handlers: [], prevVal: getValue( $elem ) };
				$( document ).on( eventNameString, function ( event ) {
					eventData = $.data( $elem[ 0 ], eventId );
					eventData.prevVal = getValue( $elem );
					$.data( $elem[ 0 ], eventId, eventData );
				} );
			}

			// Store the handler to be able to determine whether handler has been triggered already
			// when issuing a .trigger(Handler)():
			eventData.handlers.push( handleObj.handler );
			$.data( this, eventId, eventData );

			// Delegate the "eachchange" event to the supported input event(s):
			$elem.on( eventNameString, function ( event ) {
				eventData = $.data( this, eventId );

				if ( !eventData ) {
					// Event has been removed but event handler is in the loop.
					return;
				}

				event.origType = event.type;
				event.type = 'eachchange';

				handleObj.handler.call( this, event, eventData.prevVal );
			} );
		},

		remove: function ( handleObj ) {
			var eventId = EVENT_ID + handleObj.namespace;
			$( this ).off( '.' + eventId );
			$( document ).off( '.' + eventId );
			$.removeData( this, eventId );
		},

		trigger: function ( event, data ) {
			// Since the value might have changed multiple times programmatically before calling
			// .trigger(Handlers)(), the previous value will be set to the current value and
			// forwarded to the handler(s) when issuing .trigger(Handler)().
			var self = this,
				prevVal = getValue( $( this ) );

			// eslint-disable-next-line no-jquery/no-each-util
			$.each( $.data( this ), function ( eventId, eventData ) {
				if ( eventId.indexOf( EVENT_ID ) === 0 ) {
					eventData.prevVal = prevVal;
					$.data( self, eventId, eventData );
				}
			} );

			// Reset cache of already triggered handlers:
			triggeredHandlers = [];
		},

		handle: function ( event, data ) {
			if ( event.namespace !== '' ) {
				var eventData = $.data( this, EVENT_ID + event.namespace );
				if ( eventData ) {
					event.handleObj.handler.call( this, event, eventData.prevVal );
				}

			} else {
				var self = this;

				// eslint-disable-next-line no-jquery/no-each-util
				$.each( $.data( this ), function ( eventId, d ) {
					if ( eventId.indexOf( EVENT_ID ) !== 0 ) {
						// Event is not an eachchange event.
						return true;
					}

					var handlers = $.data( self, eventId ).handlers;

					for ( var i = 0; i < handlers.length; i++ ) {
						if ( !alreadyTriggered( eventId, i ) ) {
							handlers[ i ].call( self, event, d.prevVal );

							triggeredHandlers.push( {
								id: eventId,
								index: i
							} );

						}
					}

				} );
			}

			return event;
		}
	};

}() );

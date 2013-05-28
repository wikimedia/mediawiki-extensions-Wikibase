/**
 * Extension for event delegation
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( mw, wb, $, undefined ) {
	'use strict';

	/**
	 * Object extension which can add ability for event handling to other constructors/Objects.
	 * @constructor
	 * @extension
	 *
	 * @since 0.1
	 */
	wb.utilities.ObservableObject = wb.utilities.newExtension( {
		/**
		 * Triggers an event on the object, similar to jQuery.trigger()
		 *
		 * @see jQuery.trigger
		 * @return jQuery.Event the event which was triggered
		 */
		trigger: function() {
			var args = Array.prototype.slice.call( arguments ),
				event = arguments[0];

			if( typeof event !== 'object' ) {
				event = $.Event( event );
			}

			args[0] = event;
			$.fn.trigger.apply( $( this ), arguments );
			return event;
		},

		/**
		 * Triggers an event on the object, similar to jQuery.triggerHandler()
		 *
		 * @since 0.2
		 *
		 * @see jQuery.triggerHandler
		 * @return jQuery.Event the event which was triggered
		 */
		triggerHandler: function() {
			var args = Array.prototype.slice.call( arguments ),
				event = arguments[0];

			if( typeof event !== 'object' ) {
				event = $.Event( event );
			}

			args[0] = event;
			$.fn.triggerHandler.apply( $( this ), arguments );
			return event;
		},

		/**
		 * Convenience function for jQuery( this ).on()
		 *
		 * @see jQuery.on
		 */
		on: function() {
			$.fn.on.apply( $( this ), arguments );
		},

		/**
		 * Convenience function for jQuery( this ).one()
		 *
		 * @see jQuery.one
		 */
		one: function() {
			$.fn.one.apply( $( this ), arguments );
		},

		/**
		 * Convenience function for jQuery( this ).off()
		 *
		 * @see jQuery.off
		 */
		off: function() {
			$.fn.off.apply( $( this ), arguments );
		}
	} );

} )( mediaWiki, wikibase, jQuery );

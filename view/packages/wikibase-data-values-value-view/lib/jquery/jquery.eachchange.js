/**
 * eachchange jquery plugin
 * Can be used on an input element to trigger an event whenever some text was changed. This is
 * different from the native 'change' event which only fires when the input loses its focus. Once
 * called the event will be triggered for the element. Optionally a function can be given to be
 * called, but also jQuery.on( 'eachchange' ) can be used instead.
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki at snater.com >
 * @author Daniel Werner
 *
 * @example $( 'input' ).eachchange( function( event, oldValue ) { ... } );
 *
 * @dependency jquery.client
 */
( function( $, undefined ) {
	'use strict';

	/**
	 * Returns a string to be used for detecting any instant changes of an input box. In general,
	 * this should be just 'input' in recent browsers.
	 *
	 * @return {string} event(s)
	 */
	function getInputEvent() {
		// IE (at least <= version 9) does not trigger input event when pressing backspace
		// (version <= 8 does not support input event at all anyway)
		if ( $.client.profile().name === 'msie' && $.client.profile().versionNumber >= 9 ) {
			return 'input keyup';
		}

		var fallbackEvents = 'keyup keydown blur cut paste mousedown mouseup mouseout',
			$input = $( '<input/>' ),
			supported = 'oninput' in $input[0];

		return ( supported ) ? 'input' : fallbackEvents;
	}

	/**
	 * String containing all the events needed to detect any change of the input of an element.
	 * @type {string}
	 */
	var inputEvents = getInputEvent();

	$.fn.eachchange = function( fn ) {
		var monitoredInputs = $();

		var isMonitoredInput = function( input ) {
			return $.inArray( input, monitoredInputs ) >= 0;
		};

		var monitorEachChange = function( input ) {
			if( isMonitoredInput( input ) ) {
				return; // don't monitor stuff twice!
			}

			// remember, we are monitoring this from now on!
			monitoredInputs.push( input );

			var oldVal = input.val(); // old val to compare new one with
			input
			.on( inputEvents, function( e ) {
				/*
				 * NOTE: we use 'keyup' here as well, so when holding backspace the thing still gets
				 *       triggered. Also, for some reason in some browsers 'keydown' isn't triggered
				 *       when typing fast, 'keyup' always is.
				 * @TODO: Take care of context related changes via mouse (paste, drag, delete) and
				 *        DOM blur is used so at least after these changes when leaving the field,
				 *        something happens, mouseout works when dragging stuff in.
				 *        paste and mouseup only work for IE in the context menu
				 */
				// compare old value with new value and trigger 'eachchange' if it differs
				var newVal = input.val();
				if( oldVal !== newVal ) {
					input.trigger( 'eachchange', oldVal );
					oldVal = input.val();
				}
			} )
			.on( 'keydown', function( e ) {
				// store value before key evaluated to make comparison afterwards
				oldVal = input.val();
			} );
		};

		// works for text input fields and textarea only:
		this.filter( 'input, textarea' ).each( function() {
			var input = $( this );

			monitorEachChange( input );

			if( fn !== undefined ) {
				input.on( 'eachchange', fn );
			}
		} );

		return this; // return jQuery object
	};

}( jQuery ) );

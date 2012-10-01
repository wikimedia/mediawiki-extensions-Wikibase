/**
 * JavaScript for 'wikibase' extension
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 * @author Daniel Werner
 */
( function( mw, wb, $, undefined ) {
'use strict';

	/**
	 * ui related collection of jQuery extensions of the Wikibase extension
	 * @var Object
	 */
	wb.utilities.jQuery.ui = wb.utilities.jQuery.ui || {};

} )( mediaWiki, wikibase, jQuery );


/**
 * Returns a string to be used for detecting any instant changes of an input box. In general, this
 * should be just 'input' in recent browsers.
 *
 * @return String events
 */
( function( $ ) {
	$.getInputEvent = function() {
		var fallbackEvents = 'keyup keydown blur cut paste mousedown mouseup mouseout';

		// IE (at least <= version 9) does not trigger input event when pressing backspace
		// (version <= 8 does not support input event at all anyway)
		if ( $.browser.msie && parseInt( ( $.browser.version.split( '.' )[0] ), 10 ) >= 9 ) {
			return 'input keyup';
		}

		var $input = $( '<input/>' );
		var supported = 'oninput' in $input[0];
		delete $input;

		return ( supported ) ? 'input' : fallbackEvents;
	};
} )( jQuery );

/**
 * Can be used on an input element to trigger an event whenever some text was changed. This is different from the
 * native 'change' event which only fires when the input loses its focus. Once called the event will be triggered
 * for the element. Optionally a function can be given to be called, but also jQuery.on( 'eachchange' ) can be
 * used instead.
 *
 * @TODO: Take care of context related changes via mouse (paste, drag, delete) and DOM
 *
 * @example $( 'input' ).eachchange( function( event, oldValue ) { ... } );
 *
 * @author Daniel Werner
 * @version 0.1
 */
( function( $, undefined ) {
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
			.on( $.getInputEvent(), function( e ) {
				/*
				 * NOTE: we use 'keyup' here as well, so when holding backspace the thing still gets triggered. Also,
				 *       for some reason in some browsers 'keydown' isn't triggered when typing fast, 'keyup' always is.
				 * @TODO: Take care of context related changes via mouse (paste, drag, delete) and DOM
				 *        blur is used so at least after these changes when leaving the field, something happens,
				 *        mouseout works when dragging stuff in.
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

		// works for text input fields only:
		this.filter( 'input:text' ).each( function() {
			var input = $( this );

			monitorEachChange( input );

			if( fn !== undefined ) {
				input.on( 'eachchange', fn );
			}
		} );

		return this; // return jQuery object
	};
} )( jQuery );

/**
 * JavasSript for 'wikibase' extension
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file wikibase.utilities.jQuery.ui.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 * @author Daniel Werner
 */
"use strict";

/**
 * ui related collection of jQuery extensions of the Wikibase extension
 * @var Object
 */
window.wikibase.utilities.jQuery.ui = window.wikibase.utilities.jQuery.ui || {};

/**
 * Gets the width of the OS scrollbar
 *
 *! Copyright (c) 2008 Brandon Aaron (brandon.aaron@gmail.com || http://brandonaaron.net)
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * and GPL (http://www.opensource.org/licenses/gpl-license.php) licenses.
 *
 * @author Brandon Aaron (brandon.aaron@gmail.com)
 */
( function( $, undefined ) {
	var scrollbarWidth = 0;
	$.getScrollbarWidth = function() {
		if ( !scrollbarWidth ) {
			if ( $.browser.msie ) {
				var $textarea1 = $( '<textarea cols="10" rows="2"></textarea>' )
					.css( { position: 'absolute', top: -1000, left: -1000 } ).appendTo( 'body' ),
					$textarea2 = $( '<textarea cols="10" rows="2" style="overflow: hidden;"></textarea>' )
						.css( { position: 'absolute', top: -1000, left: -1000 } ).appendTo( 'body' );
				scrollbarWidth = $textarea1.width() - $textarea2.width();
				$textarea1.add( $textarea2 ).remove();
			} else {
				var $div = $( '<div />' )
					.css( { width: 100, height: 100, overflow: 'auto', position: 'absolute', top: -1000, left: -1000 } )
					.prependTo( 'body' ).append( '<div />' ).find( 'div' )
					.css( { width: '100%', height: 200 } );
				scrollbarWidth = 100 - $div.width();
				$div.parent().remove();
			}
		}
		return scrollbarWidth;
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
			.on( 'keyup keydown mouseout blur paste mouseup', function( e ) {
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

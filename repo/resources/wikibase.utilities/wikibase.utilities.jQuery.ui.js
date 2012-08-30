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
"use strict";

/**
 * ui related collection of jQuery extensions of the Wikibase extension
 * @var Object
 */
window.wikibase.utilities.jQuery.ui = window.wikibase.utilities.jQuery.ui || {};

/**
 * Gets the width of the browser's scrollbar.
 */
( function( $ ) {
	$.getScrollbarWidth = function() {
		var $inner = $( '<p/>', {
			style: 'width:100px'
		} ),
		$outer = $( '<div/>', {
			style: 'position:absolute;top:-1000px;left:-1000px;visibility:hidden;width:50px;height:50px;overflow:hidden;'
		} ).append( $inner ).appendTo( $( 'body' ) );
		var majorWidth = $inner.width();
		$outer.css( 'overflow', 'scroll' );
		var minorWidth = $inner.width();
		if ( majorWidth === minorWidth ) { // Webkit
			minorWidth = $outer[0].clientWidth;
		}
		$outer.remove();
		return ( majorWidth - minorWidth );
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
	var storeVal = function( input ) {
		input.data( 'eachchange.oldVal', input.val() );
	}

	$.fn.eachchange = function( fn ) {
		// works for text input fields only:

		this.filter( 'input:text' ).each( function() {
			var input = $( this );
			storeVal( input ); // store value so 'eachchange' can be triggered for first change
			if( fn !== undefined ) {
				input.on( 'eachchange', fn );
			}
		} );

		return this; // return jQuery object
	};


	// remember old value for all inputs initially:
	$( 'input' ).each( function() {
		$( this ).data( 'eachchange.oldVal', $( this ).val() );
	} );

	// delegate all input events to figure out 'eachchange':
	$( 'body' )
	.on( 'keyup keydown mouseout blur paste mouseup', 'input', function( event ) {
		var input = $( event.target ),
			oldVal = input.data( 'eachchange.oldVal' ), // will be null if this is a new input element! TODO: can we improve this?
			newVal = input.val();

		/*
		 * NOTE: we use 'keyup' here as well, so when holding backspace the thing still gets triggered. Also,
		 *       for some reason in some browsers 'keydown' isn't triggered when typing fast, 'keyup' always is.
		 * @TODO: Take care of context related changes via mouse (paste, drag, delete) and DOM
		 *        blur is used so at least after these changes when leaving the field, something happens,
		 *        mouseout works when dragging stuff in.
		 *        paste and mouseup only work for IE in the context menu
		 */
		// compare old value with new value and trigger 'eachchange' if it differs
		if( oldVal !== newVal ) {
			// trigger 'eachchange' event on original input element
			input.trigger( 'eachchange', oldVal );

			// remember new value for next change
			storeVal( input );
		}
	} )
	.on( 'keydown', 'input', function( event ) {
		// store value before key evaluated to make comparison afterwards
		storeVal( $( event.target ) );
	} );

} )( jQuery );

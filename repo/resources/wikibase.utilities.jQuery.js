/**
 * JavasSript for 'wikibase' extension
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.utilities.jQuery.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
"use strict";

window.wikibase.utilities = {};

// Wikibase jQuery plugins:
( function( $ ){

	/**
	 * Can be used on input elements. They grow bigger when input is registered and shrinks when something was removed.
	 *
	 * Based on autoGrowInput plugin by James Padolsey's
	 * See related thread: http://stackoverflow.com/questions/931207/is-there-a-jquery-autogrow-plugin-for-text-fields
	 *
	 * @example $( 'input' ).inputAutoExpand();
 	 */
	$.fn.inputAutoExpand = function( options ) {

		var o = $.extend( {
			maxWidth: 1000,
			minWidth: 0,
			comfortZone: 70
		}, options );

		// only expand input fields:
		this.filter( 'input:text' ).each( function() {

			var minWidth = o.minWidth || $( this ).width();
			var val = '';
			var input = $( this );
			var ruler = $( '<div/>' ).css( {
				position: 'absolute',
				top: -99999,
				left: -99999,
				width: 'auto',
				fontSize: input.css( 'fontSize' ),
				fontFamily: input.css( 'fontFamily' ),
				fontWeight: input.css( 'fontWeight' ),
				letterSpacing: input.css( 'letterSpacing' ),
				whiteSpace: 'nowrap'
			} );

			var expand = function() {
				if( val === ( val = input.val() ) ) {
					return;
				}

				// Take text from input and put it into our dummy
				// insert ruler and remove it again
				ruler.insertAfter( input );
				ruler.html( val // escape stuff
					.replace(/&/g, '&amp;')
					.replace(/</g, '&lt;')
					.replace(/\s/g,'&nbsp;')
					.replace(/\s/g,'&nbsp;')
				);
				var rulerWidth = ruler.width();
				ruler.remove();

				// Calculate new width + whether to change
				var newWidth = ( rulerWidth + o.comfortZone ) >= minWidth ? rulerWidth + o.comfortZone : minWidth;
				var currentWidth = input.width();

				var isValidWidthChange =
						( newWidth < currentWidth && newWidth >= minWidth )
						|| ( newWidth > minWidth && newWidth < o.maxWidth );

				// Animate width
				if( isValidWidthChange ) {
					input.width( newWidth );
				}
			};

			$( this ).on( 'keyup keydown blur update', expand );

		} );

		return this;
	};

} )( jQuery );

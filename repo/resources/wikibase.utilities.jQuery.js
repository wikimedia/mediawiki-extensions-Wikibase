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
	 * @param object options set of options with the following (optional) keys:
	 *        'maxWidth':         number|function|jQuery - maximum width allowed.
	 *        'minWidth':         number|function|jQuery - minimum width allowed.
	 *        'comfortZone':      number|function|jQuery - space left free behind the input.
	 *        'widthCalculation': function(input) - function which receives the input element as first parameter.
	 *                            has to return the width considered the current width.
	 *
	 * @example $( 'input' ).inputAutoExpand();
	 */
	$.fn.inputAutoExpand = function( options ) {

		var o = $.extend( {
			maxWidth: 1000,
			minWidth: 0,
			comfortZone: 70,
			widthCalculation: function( width ) { return width },

			getMaxWidth: function() {
				return this.normalizeWidth( this.maxWidth );
			},
			getMinWidth: function() {
				return this.normalizeWidth( this.minWidth );
			},
			getComfortZone: function() {
				return this.normalizeWidth( this.comfortZone );
			},

			/**
			 * Normalizes the width, allowing integers as well as objects to get their current width as return value.
			 * This can also be a callback to return the value.
			 *
			 * @param number|function|jQuery width
			 * @return number
			 */
			normalizeWidth: function( width ) {
				if( $.isFunction( width ) ) {
					return width();
				}
				if( width instanceof $ ) {
					width = width.width();
				}
				return width;
			}
		}, options );

		// only expand input fields:
		this.filter( 'input:text' ).each( function() {

			var minWidth = o.getMinWidth() || $( this ).width();
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
				var maxWidth = o.getMaxWidth()
				var newWidth = ( rulerWidth + o.getComfortZone() ) >= minWidth ? rulerWidth + o.getComfortZone() : minWidth;
				var newInputWidth = newWidth; // pure width of the input
				newWidth = o.widthCalculation( newWidth );
				var currentWidth = o.widthCalculation( input.width() );

				var isValidWidthChange =
						( newWidth < currentWidth && newWidth >= minWidth )
						|| ( newWidth > minWidth && newWidth < maxWidth );

				// Animate width
				if( isValidWidthChange ) {
					input.width( newInputWidth );
				}
				else if( maxWidth < newWidth ) {
					input.width( maxWidth - o.widthCalculation( 0 ) );
				}
			};

			expand(); // set width initially
			$( this ).on( 'keyup keydown blur update', expand );

		} );

		return this;
	};

} )( jQuery );

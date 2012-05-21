/**
 * jQuery plugins for 'wikibase' extension
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.utilities.jQuery.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater
 */
'use strict';

window.wikibase.utilities = {};

( function( $ ) {
	/**
	 * extension of jquery.ui.autocomplete
	 *
	 * @example $( 'input' ).wikibaseAutocomplete( { source: ['a', 'b', 'c'] });
	 */
	$.fn.wikibaseAutocomplete = function( options ) {
		/**
		 * how many items the dropdown should containg before toggling scrollbar
		 * @const int
		 */
		var MAX_ITEMS = 10;

		this.filter( 'input:text' ).each( function() {
			$( this ).autocomplete( options )
				.on( 'autocompleteopen', $.proxy( function( event ) {
					// resize menu height to height of MAX_ITEMS
					var menu = this.data('autocomplete').menu.element;
					menu.css( 'minWidth', 'auto' );
					if ( menu.children().length > MAX_ITEMS ) {
						var fixedHeight = 0;
						for ( var i = 0; i < MAX_ITEMS ; i++ ) {
							fixedHeight += $( menu.children()[i] ).height();
						}
						menu.width( menu.width() + $.getScrollbarWidth() );
						menu.height( fixedHeight );
						menu.css( 'overflowY', 'scroll' );
					} else {
						menu.width( 'auto' );
						menu.height( 'auto' );
						menu.css( 'overflowY', 'auto' );
					}
					menu.css( 'minWidth', this.data('autocomplete').element.outerWidth() - ( menu.outerWidth() - menu.width() ) + 'px' );
				}, $( this ) ) );
		} );
		return this;
	}
} )( jQuery );


/**
 * Gets the width of the OS scrollbar
 *
 *! Copyright (c) 2008 Brandon Aaron (brandon.aaron@gmail.com || http://brandonaaron.net)
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * and GPL (http://www.opensource.org/licenses/gpl-license.php) licenses.
 */
( function( $ ) {
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


( function( $ ) {

	/**
	 * Can be used on input elements. They grow bigger when input is registered and shrinks when something was removed.
	 *
	 * Based on autoGrowInput plugin by James Padolsey's
	 * See related thread: http://stackoverflow.com/questions/931207/is-there-a-jquery-autogrow-plugin-for-text-fields
	 *
	 * @param object options set of options with the following (optional) keys:
	 *        'maxWidth':         number|function|jQuery - maximum width allowed.
	 *        'minWidth':         number|function|jQuery - minimum width allowed. If not set, the space required by the
	 *                            input elements placeholder text will be datermined automatically.
	 *        'comfortZone':      number|function|jQuery - space left free behind the input.
	 *        'widthCalculation': function(width) - function to calculate the width, for example to add some additional
	 *                            space for other elements which should be considered when calculating remaining space.
	 *
	 * @example $( 'input' ).inputAutoExpand();
	 */
	$.fn.inputAutoExpand = function( options ) {

		var o = $.extend( {
			maxWidth: 1000,
			minWidth: false,
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

		var autoMinWidth = o.minWidth === false;

		// only expand input fields:
		this.filter( 'input:text' ).each( function() {

			//var minWidth = o.getMinWidth() || $( this ).width();
			var input = $( this );
			var val = input.val();
			var ruler = $( '<div/>' ).css( {
				position: 'absolute',
				top: -9999,
				left: -9999,
				width: 'auto',
				fontSize: input.css( 'fontSize' ),
				fontFamily: input.css( 'fontFamily' ),
				fontWeight: input.css( 'fontWeight' ),
				letterSpacing: input.css( 'letterSpacing' ),
				whiteSpace: 'nowrap'
			} );

			var expand = function() {
				if( val === '' && input.attr( 'placeholder' ) && !input.is( ':focus' ) ) {
					// if empty and not focused, make sure placeholder text will be displayed
					val = input.attr( 'placeholder' );
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

				var minWidth = o.getMinWidth();
				var maxWidth = o.getMaxWidth();

				// pure width of the input, without additional calculation
				var newInputWidth = ( rulerWidth + o.getComfortZone() ) >= minWidth ? rulerWidth + o.getComfortZone() : minWidth;
				var newWidth = o.widthCalculation( newInputWidth );

				var currentWidth = o.widthCalculation( input.width() );

				// Calculate new width + whether to change
				var isValidWidthChange =
						( newWidth < currentWidth && newWidth >= minWidth )
						|| ( newWidth > minWidth && newWidth < maxWidth );

				// Animate width
				if( isValidWidthChange ) {
					input.width( newInputWidth );
				}
				else if( maxWidth < newWidth ) {
					// make sure we set the width if the content is too long from the start
					input.width( maxWidth - o.widthCalculation( 0 ) );
				}

				if( o.minWidth === false ) {
					// no explicit min-width given, set this to the text required by placeholder
					o.minWidth = input.width();
				}
			};

			// if no min width given, set min width to placeholder text. This will also prevent impediments with
			// growing/shrinking boxes on blur when intending to click some button but after the blur the mouse click
			// event isn't fired because the mouseup has been registered on some other element.
			if( o.minWidth === false && input.attr( 'placeholder' ) ) {
				var origVal = val;
				val = input.attr( 'placeholder' );
				// first call to provoke placeholder being handled to datermine min width:
				expand();
				val = origVal;
			} else {
				o.minWidth = 0;
			}

			expand(); // set width initially

			// set width on all important related events:
			$( this )
			.on( 'keyup keydown blur update', function() {
				if( val !== ( val = input.val() ) ) {
					expand();
				}
			} )
		} );

		return this;
	};

} )( jQuery );

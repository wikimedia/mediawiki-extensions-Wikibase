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
( function( $ ) {

	/**
	 * Can be used on input elements. They grow bigger when input is registered and shrinks when something was removed.
	 *
	 * Based on autoGrowInput plugin by James Padolsey's
	 * See related thread: http://stackoverflow.com/questions/931207/is-there-a-jquery-autogrow-plugin-for-text-fields
	 *
	 * @param object options set of options with the following (optional) keys:
	 *        'maxWidth':         number|function|jQuery - maximum width allowed.
	 *        'minWidth':         number|function|jQuery|false - minimum width allowed. If not set or false, the space
	 *                            required by the input elements placeholder text will be determined automatically.
	 *        'comfortZone':      number|function|jQuery|false - space left free behind the input. if not set or false,
	 *                            an appropriate amount of space will be calculated automatically.
	 *        'widthCalculation': function(width) - function to calculate the width, for example to add some additional
	 *                            space for other elements which should be considered when calculating remaining space.
	 *
	 * @example $( 'input' ).inputAutoExpand();
	 */
	$.fn.inputAutoExpand = function( options ) {
		if( ! options ) {
			options = {};
		}

		// inject default options for missing ones:
		var fullOptions = $.extend( {
			maxWidth: 1000,
			minWidth: false, // dynamic: length of placeholder or 0 if no placeholder
			comfortZone: false,
			widthCalculation: function( width ) { return width }
		}, options );

		// expand input fields:
		this.filter( 'input:text' ).each( function() {
			var input = $( this );
			var inputAE = input.data( 'AutoExpandInput' );

			if( inputAE ) {
				// AutoExpand initialized already, update options only (will also expand)
				inputAE.setOptions( options );
			} else {
				// initialize new auto expand:
				new AutoExpandInput( this, fullOptions );
			}
		} );

		return this;
	};

	/**
	 * Prototype for auto expanding input elements.
	 * @constructor
	 *
	 * @param inputElem
	 * @param options
	 */
	var AutoExpandInput = function( inputElem, options ) {
		this.input = $( inputElem );
		this._val = this.input.val();
		this._o = options;

		this.input.data( 'AutoExpandInput', this );

		this.expand(); // calculate width initially

		var self = this;

		// set width on all important related events:
		$( this.input )
		.on( 'keyup keydown blur focus update', function() { //TODO: doesn't yet update its width when new value set via JS
			if( self._val !== ( self._val = self.input.val() ) ) {
				self.expand();
			}
		} );

		// make sure size will adjust on resize:
		( function() {
			var oldWidth;
			var resizeHandler = function() {
				if( ! self.input.data( 'AutoExpandInput' ) ) {
					// remove() must have been called on input, data was removed, remove handler from window!
					$( window ).off( 'resize', resizeHandler );
					return;
				}
				if( ! self.input.closest('html').length ) {
					// if input doesn't exist in DOM, no resize necessary
					oldWidth = null;
					return;
				}

				var newWidth = $( this ).width();

				// only resize if width has been resized!
				if( oldWidth !== newWidth ) {
					self.expand();
				}
				oldWidth = newWidth;
			}

			$( window ).on( 'resize', resizeHandler );
		} )();
	}

	AutoExpandInput.prototype = {
		/**
		 * sets the input boxes width to fit the boxes content.
		 *
		 * @return number how much the input with grew. If the value is negative, it shrank.
		 */
		expand: function() {
			var minWidth = this.getMinWidth(),
				maxWidth = this.getMaxWidth(),
				comfortZone = this.getComfortZone();

			var valWidth = this.getWidthFor( this._val ) + comfortZone;

			// pure width of the input, without additional calculation
			var newInputWidth = ( valWidth + comfortZone ) > minWidth ? valWidth + comfortZone : minWidth,
				newWidth = this._o.widthCalculation( newInputWidth ),
				oldWidth = this.input.width();

			// Calculate new width + whether to change
			var isValidWidthChange =
					( newWidth < oldWidth && newWidth >= minWidth )
					|| ( newWidth > minWidth && newWidth < maxWidth );

			// Animate width
			if( isValidWidthChange ) {
				this.input.width( newInputWidth );
			}
			else if( maxWidth < newWidth ) {
				// make sure we set the width if the content is too long from the start
				this.input.width( maxWidth - this._o.widthCalculation( 0 ) );
			}

			// return change
			return this.input.width() - oldWidth;
		},

		/**
		 * Calculates the width which would be required for the input field if the given text were inserted.
		 * This does not consider the comfort zone given in the options and doesn't check for min/max width restraints.
		 *
		 * @param string text
		 * @return string
		 */
		getWidthFor: function( text ) {
			var input = this.input;

			// Take text from input and put it into our dummy
			// insert ruler and remove it again
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

			ruler.insertAfter( input );
			ruler.html( text // escape stuff
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/\s/g,'&nbsp;')
			);
			var rulerWidth = ruler.width();
			ruler.remove();

			return rulerWidth;
		},

		getMaxWidth: function() {
			return this._normalizeWidth( this._o.maxWidth );
		},

		getMinWidth: function() {
			var width = this._o.minWidth;

			if( width === false ) {
				// dynamic min width, depending on placeholder...
				// always calculate in case placeholder changes!
				if( ! this.input.attr( 'placeholder' ) ) {
					return 0; // ... or 0 if no placeholder
				}
				// don't need comfort zone in this case just some sane space
				return this.getWidthFor( this.input.attr( 'placeholder' ) + ' ' );
			}
			return this._normalizeWidth( width );
		},

		getComfortZone: function() {
			if( this._o.comfortZone === false ) {
				// automatic comfort zone, calculate
				// average of some usually broader characters
				return this.getWidthFor( '@%_MW' ) / 5;
			}
			return this._normalizeWidth( this._o.comfortZone );
		},

		/**
		 * Normalizes the width, allowing integers as well as objects to get their current width as return value.
		 * This can also be a callback to return the value.
		 *
		 * @param number|function|jQuery width
		 * @param jQuery elem
		 * @return number
		 */
		_normalizeWidth: function( width ) {
			if( $.isFunction( width ) ) {
				return width.call( this );
			}
			if( width instanceof $ ) {
				width = width.width();
			}
			return width;
		},

		getOptions: function() {
			return this._o;
		},

		/**
		 * Updates the options of the object. After the options are set, expand() will be called.
		 *
		 * @param Array options one or more options which will extend the current options.
		 */
		setOptions: function( options ) {
			this._o = $.extend( this._o, options );
			this.expand();
		}
	}

} )( jQuery );

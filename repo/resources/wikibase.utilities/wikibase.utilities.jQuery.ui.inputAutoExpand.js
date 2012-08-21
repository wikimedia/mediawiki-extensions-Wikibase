/**
 * JavaScript for 'wikibase' extension
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
"use strict";

// Wikibase jQuery plugin for auto expanding input boxes:
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
			expandOnResize: true // whether width should be re-calculated when browser window has been resized
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
				var autoExpandInput = new AutoExpandInput( this, fullOptions );
				$( this ).data( 'AutoExpandInput', autoExpandInput );
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
		this._o = options;

		this.expand(); // calculate width initially

		var self = this;

		var domCheck = function() {
			return !!self.input.closest( 'html' ).length; // false if input is not in DOM
		};

		if( ! domCheck() ) {
			// use timeout till input is in DOM. This might not be the prettiest way but seems necessary in some situations.
			window.setTimeout( function() {
				if( domCheck() ) {
					window.clearTimeout( this );
					self.expand();
				}
			}, 10 );
		}

		// set width on all important related events:
		$( this.input )
		.eachchange( function( e, oldValue ) {
			// NOTE/FIXME: won't be triggered if placeholder has changed (via JS) but not input text
			self.expand();
		} );

		// make sure box will consider window size after resize:
		AutoExpandInput.activateResizeHandler();
	}

	/**
	 * Once called, this will make sure AutoExpandInput's will adjust on resize. When called for a second time
	 * the resize handler will not be initialized again.
	 *
	 * @return bool false if the handler was active before.
	 */
	AutoExpandInput.activateResizeHandler = function() {
		if( AutoExpandInput.activateResizeHandler.active ) {
			return false; // don't initialize this more than once
		}
		AutoExpandInput.activateResizeHandler.active = true;

		( function() {
			var oldWidth; // width before resize
			var resizeHandler = function() {
				var newWidth = $( this ).width();

				if( oldWidth === newWidth ) {
					// no change in horizontal width after resize
					return;
				}

				$.each( AutoExpandInput.getActiveInstances(), function() {
					// NOTE: this could be more efficient in case many inputs are set. We could just calculate the
					// inputs (new) max-width and check whether it is exceeded in which case we set it to the max width.
					// Basically the same but other way around for minWidth.
					if( this.getOptions().expandOnResize ) {
						this.expand();
					}
				} );

				oldWidth = newWidth;
			};

			$( window ).on( 'resize', resizeHandler );
		} )();

		return true;
	}

	/**
	 * Returns all active instances whose related input is still in the DOM
	 *
	 * @return AutoExpandInput[]
	 */
	AutoExpandInput.getActiveInstances = function() {
		var instances = new Array();

		// get all AutoExpandInput by checking input $.data(). If $.remove() was called, the data was removed!
		$( 'input' ).each( function() {
			var instance = $.data( this, 'AutoExpandInput' );
			if( instance ) {
				instances.push( instance );
			}
		} );

		return instances;
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

			// give min width higher priority than max width:
			maxWidth = ( maxWidth > minWidth ) ? maxWidth : minWidth;

			//console.log( '=== START EXPANSION ===' );
			//console.log( 'min: ' + minWidth + ' | max: ' + maxWidth + ' | comfort: ' + comfortZone );

			var val = this.input.val();
			var valWidth = this.getWidthFor( val ); // pure width of the input, without additional calculation

			//console.log( 'valWidth: ' + valWidth + ' | val: ' + val );

			// add comfort zone or take min-width if too short
			var newWidth = ( valWidth + comfortZone ) > minWidth ? valWidth + comfortZone : minWidth,
				oldWidth = this.getWidth();

			if( newWidth >= maxWidth  ) {
				// NOTE: check for this in all cases, FF had some bug not returning false for isValidWidthChange due to some floating point issues apparently
				// make sure we set the width if the content is too long from the start
				this.input.width( maxWidth );
				//console.log( 'set to max width!' );
			}
			else {
				// Calculate new width + whether to change
				var isValidWidthChange =
						( newWidth < oldWidth && newWidth >= minWidth )
						|| ( newWidth >= minWidth && newWidth < maxWidth );

				//console.log( 'newWidth: ' + newWidth + ' | oldWidth: ' + oldWidth + ' | isValidChange: ' + ( isValidWidthChange ? 'true' : 'false' ) );

				// Animate width
				if( isValidWidthChange ) {
					this.input.width( newWidth );
					//console.log( 'set to calculated width!' );
				}
			}

			//console.log( '=== END EXPANSION (' + ( this.getWidth() - oldWidth ) + ') ===' );

			// return change
			return this.getWidth() - oldWidth;
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
			// consider padding of input
			var rulerWidth = ruler.width() + ( input.innerWidth() - input.width() );
			ruler.remove();

			return rulerWidth;
		},

		/**
		 * Returns the current width.
		 *
		 * @return number
		 */
		getWidth: function() {
			return this._normalizeWidth( this.input.width() );
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
				width = this.getWidthFor( this.input.attr( 'placeholder' ) + ' ' );
			}
			return this._normalizeWidth( width );
		},

		getComfortZone: function() {
			var width = this._o.comfortZone;
			if( width === false ) {
				// automatic comfort zone, calculate
				// average of some usually broader characters
				width = this.getWidthFor( '@%_MW' ) / 5 * 1.25;
			}
			return this._normalizeWidth( width );
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
			return Math.round( width );
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
	};

} )( jQuery );

/**
 * Makes input or textarea elements automatically expand/contract their size according to their
 * input while typing. Vertical resizing will of course work for textareas only.
 *
 * Based on autoGrowInput plugin by James Padolsey
 * (see: http://stackoverflow.com/questions/931207/is-there-a-jquery-autogrow-plugin-for-text-fields)
 * and Autosize plugin by Jack Moore (license: MIT)
 * (see: http://www.jacklmoore.com/autosize)
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki at snater.com >
 *
 * @since 0.1 (moved from WikibaseLib 0.4 alpha)
 *
 * @example $( 'input' ).inputAutoExpand();
 * @desc Enhances an input element with horiontal auto-expanding functionality.
 *
 * @example $( 'textarea' ).inputAutoExpand( { expandWidth: false, expandHeight: true } );
 * @desc Enhances an input element with vertical auto-expanding functionality.
 *
 * @option expandWidth {Boolean} Whether to horizontally expand/contract the input element.
 *         default: true
 *
 * @option expandHeight {Boolean} Whether to vertically expand/contract the input element.
 *         default: false
 *
 * @option maxWidth {Number} Maximum width the input element may stretch.
 *         default: 1000
 *
 * @option minWidth {Number} Minimum width. If not set or false, the space required by the input
 *         elements placeholder text will be determined automatically (taking placeholder into
 *         account).
 *         default: false
 *
 * @option maxHeigth {Number} Maximum height the input element may stretch. Set to false for not
 *         constraining the height to a  maximum.
 *         default: false
 *
 * @option minHeight {Number} Minimum height. Set to false for not constraining the height to a
 *         minimum.
 *         default: false
 *
 * @option comfortZone {Number} White space behind the input text. If set to false, an
 *         appropriate amount of space will be calculated automatically.
 *         default: false
 *
 * @option expandOnResize {Boolean} Whether width should be re-calculated when the browser
 *         window has been resized.
 *         default: true
 *
 * @option suppressNewLine {Boolean} Whether to suppress new-line characters.
 *         default: false
 *
 * @dependency jquery.eachchange
 *
 * @todo Make expandWidth and expandHeight work simultaneously.
 * @todo Destroy mechanism
 */
( function( $ ) {
	'use strict';

	/**
	 * Tests if the user client is capable of assigning a height of 0 to a textarea. (E.g. Firefox
	 * on Mac will always set the minimum height to the text height as long as the textarea is
	 * attached to the body element.)
	 *
	 * @return {boolean}
	 */
	function supports0Height() {
		var support = true,
			$t = $( '<textarea/>' );

		$t.attr( 'style', 'height: 0 !important; width: 0 !important; top:-9999px; left: -9999px;' )
		.text( 'text' )
		.appendTo( $( 'body' ) );

		if( $t.height() >= 1 ) { // addressing rounding
			support = false;
		}
		$t.remove();

		return support;
	}

	/**
	 * Whether the user client is capable of setting the textarea height to 0.
	 * @type {boolean}
	 */
	var browserSupports0Height;

	$( document ).ready( function() {
		browserSupports0Height = supports0Height();
	} );


	$.fn.inputAutoExpand = function( options ) {
		if( ! options ) {
			options = {};
		}

		// inject default options for missing ones:
		var fullOptions = $.extend( {
			expandWidth: true,
			expandHeight: false,
			maxWidth: 1000,
			minWidth: false,
			maxHeight: false,
			minHeight: false,
			comfortZone: false,
			expandOnResize: true,
			suppressNewLine: false
		}, options );

		// expand input fields:
		this.filter( 'input:text, textarea' ).each( function() {
			var input = $( this );
			var inputAE = input.data( 'AutoExpandInput' );

			if( inputAE ) {
				// AutoExpand initialized already, update options only (will also expand)
				if( options ) {
					inputAE.setOptions( options ); // also triggers re-calculation of width
				} else {
					inputAE.expand();
				}

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
		this._active = false;

		var self = this;

		var domCheck = function() {
			return !!self.input.closest( 'html' ).length; // false if input is not in DOM
		};

		if( ! domCheck() ) {
			// use timeout till input is in DOM. This might not be the prettiest way but seems
			// necessary in some situations.
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

			// Got to check on each change since value might have been pasted or dragged into the
			// input element
			if ( self._o.suppressNewLine ) {
				self.input.val( self.input.val().replace( /\r?\n/g, '' ) );
			}
			self.expand();
		} );

		var rulers = AutoExpandInput.initRulers();
		this.$rulerX = rulers[0];
		this.$rulerY = rulers[1];

		this.expand(); // calculate width initially

		// do not show any resize handle for manual resizing
		this.input.css( 'resize', 'none' );

		// make sure box will consider window size after resize:
		AutoExpandInput.activateResizeHandler();
	};

	AutoExpandInput.initRulers = function() {
		var $rulerX = $( '#AutoExpandInput_rulerX' );
		if ( !$rulerX.length ) {
			$rulerX = $( '<div/>' )
				.attr( 'id', 'AutoExpandInput_rulerX' )
				.css( {
					width: 'auto',
					whiteSpace: 'nowrap',
					position: 'absolute',
					top: '-9999px',
					left: '-9999px',
					visibility: 'hidden',
					display: 'none'
				} )
				.appendTo( 'body' );
		}

		var $rulerY = $( '#AutoExpandInput_rulerY' );
		if ( !$rulerY.length ) {
			$rulerY = $( '<textarea style="minHeight: 0!important; height: 0!important;"/>' )
				.attr( 'id', 'AutoExpandInput_rulerY' )
				.attr( 'tabindex', '-1' )
				.css( {
					position: 'absolute',
					top: '-9999px',
					left: '-9999px',
					right: 'auto',
					bottom: 'auto',
					wordWrap: 'break-word'
				} )
				.appendTo( 'body' );
		}

		return [$rulerX, $rulerY];
	};

	/**
	 * Once called, this will make sure AutoExpandInput's will adjust on resize. When called for a
	 * second time the resize handler will not be initialized again.
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
					// NOTE: this could be more efficient in case many inputs are set. We could just
					//  calculate the inputs (new) max-width and check whether it is exceeded in
					//  which case we set it to the max width. Basically the same but other way
					//  around for minWidth.
					if( this.getOptions().expandOnResize ) {
						this.expand();
					}
				} );

				oldWidth = newWidth;
			};

			$( window ).on( 'resize', resizeHandler );
		}() );

		return true;
	};

	/**
	 * Returns all active instances whose related input is still in the DOM
	 *
	 * @return AutoExpandInput[]
	 */
	AutoExpandInput.getActiveInstances = function() {
		var instances = [];

		// get all AutoExpandInput by checking input $.data().
		// If $.remove() was called, the data was removed!
		$( 'input' ).each( function() {
			var instance = $.data( this, 'AutoExpandInput' );
			if( instance ) {
				instances.push( instance );
			}
		} );

		return instances;
	};

	$.extend( AutoExpandInput.prototype, {
		/**
		 * sets the input boxes width to fit the boxes content.
		 *
		 * @return number how much the input with grew. If the value is negative, it shrank.
		 */
		expand: function() {

			if ( this._o.expandWidth ) {
				this.copyStyles( this.$rulerX );
			}
			if ( this._o.expandHeight ) {
				this.copyStyles( this.$rulerY );
			}

			if ( this._o.expandWidth ) {
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
				var newWidth = ( valWidth + comfortZone ) > minWidth
						? valWidth + comfortZone
						: minWidth,
					oldWidth = this.getWidth();

				if( newWidth >= maxWidth  ) {
					// NOTE: check for this in all cases, FF had some bug not returning false for
					//  isValidWidthChange due to some floating point issues apparently make sure
					//  we set the width if the content is too long from the start.
					this.input.width( maxWidth );
					//console.log( 'set to max width!' );
				}
				else {
					// Calculate new width + whether to change
					var isValidWidthChange =
							( newWidth < oldWidth && newWidth >= minWidth )
							|| ( newWidth >= minWidth && newWidth < maxWidth );

					//console.log( 'newWidth: ' + newWidth + ' | oldWidth: ' + oldWidth
					//	+ ' | isValidChange: ' + ( isValidWidthChange ? 'true' : 'false' ) );

					// Animate width
					if( isValidWidthChange ) {
						this.input.width( newWidth );
						//console.log( 'set to calculated width!' );
					}
				}

				//console.log( '=== END EXPANSION (' + ( this.getWidth() - oldWidth ) + ') ===' );

				// return change
				return this.getWidth() - oldWidth;
			}
			if ( this._o.expandHeight ) {
				var valHeight = this.getHeightFor( this.input.val() ),
					input = this.input[0],
					minHeight = this._o.minHeight || 0, // will keep one line in any case
					maxHeight = this._o.maxHeight,
					oldHeight = this.input.height();

				if ( maxHeight && valHeight > maxHeight ) {
					input.style.height = maxHeight + 'px';
					input.style.overflow = 'scroll';
				} else {
					if ( minHeight && valHeight < minHeight ) {
						input.style.height = minHeight + 'px';
					} else {
						input.style.height = ( !isNaN( valHeight ) ? valHeight : 0 ) + 'px';
					}
					this.input.css( 'overflow', 'hidden' );
				}
				return valHeight - oldHeight;
			}
		},

		/**
		 * Copy styles that affect spacing from the original element to the element used to measure
		 * any size change.
		 *
		 * @param {jQuery} $to Element used to determine the size change
		 */
		copyStyles: function( $to ) {
			// line-height is omitted because IE7/IE8 doesn't return the correct value.
			var $input = this.input,
				stylesToCopy = [
					'fontFamily',
					'fontSize',
					'fontWeight',
					'fontStyle',
					'letterSpacing',
					'textTransform',
					'wordSpacing',
					'textIndent',
					'overflowY'
				];

			// test that line-height can be accurately copied to avoid
			// incorrect value reporting in old IE and old Opera
			$to.css( 'lineHeight', '99px' );
			if ( $to.css( 'lineHeight' ) === '99px' ) {
				stylesToCopy.push( 'lineHeight' );
			}

			$.each( stylesToCopy, function( i, styleName ) {
				$to.css( styleName, $input.css( styleName ) );
			} );

			// styles not being influenced by copying styles
			$to.css( {
				overflow: 'hidden',
				overflowY: 'hidden',
				wordWrap: 'break-word'
			} );
		},

		/**
		 * Calculates the width which would be required for the input field if the given text were
		 * inserted. This does not consider the comfort zone given in the options and doesn't check
		 * for min/max width restraints.
		 *
		 * @param {String} text
		 * @return {String}
		 */
		getWidthFor: function( text ) {
			var input = this.input,
				ruler = this.$rulerX;

			ruler.html( text // escape stuff
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/\s/g,'&nbsp;')
			);

			return ruler.width();
		},

		/**
		 * Calculates the height the given text would require to not show any scrollbar within the
		 * input element.
		 *
		 * @param {String} text
		 */
		getHeightFor: function( text ) {
			var active = this._active;

			if ( !active ) {
				active = true;

				var input = this.input[0],
					ruler = this.$rulerY[0];

				ruler.value = text;

				// Update the width in case the original textarea width has changed
				ruler.style.width = this.input.width() + 'px';

				// Needed for IE to reliably return the correct scrollHeight
				ruler.scrollTop = 0;

				// Set a very high value for scrollTop to be sure the
				// mirror is scrolled all the way to the bottom.
				ruler.scrollTop = 9e4;

				// This small timeout gives IE a chance to draw its scrollbar
				// before adjust can be run again (prevents an infinite loop).
				setTimeout( function () {
					active = false;
				}, 10 );

				var border = parseInt( this.input.css( 'borderTopWidth' ), 10 )
					+ parseInt( this.input.css( 'borderBottomWidth' ), 10 );

				return ( browserSupports0Height )
					? ruler.scrollTop + border
					: ruler.scrollTop + border + ruler.clientHeight;
			}
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
				// this is much faster for getting a good estimation for the perfect comfort zone
				// compared to the method where we did "this.getWidthFor( '@%_MW' ) / 5 * 1.25;"
				width = this.input.height();
			}
			return this._normalizeWidth( width );
		},

		/**
		 * Normalizes the width, allowing integers as well as objects to get their current width as
		 * return value. This can also be a callback to return the value.
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
			// round it up to avoid issues where we can't round down because it wouldn't fit
			return Math.ceil( width );
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

	} );

}( jQuery ) );

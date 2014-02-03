/**
 * jQuery.inputautoexpand plugin
 *
 * Makes input or textarea elements automatically expand/contract their size according to their
 * input value while typing. Vertical resizing will of course work for textareas only.
 * The input/textarea element the plugin is initialized on needs to be in the DOM in order to be
 * able to correctly detect the element's native width.
 * Compatibility: IE >= 8
 *
 * Based on:
 * - autoGrowInput plugin by James Padolsey (http://jsbin.com/ahaxe)
 * - Autosize plugin by Jack Moore (licence: MIT) (http://www.jacklmoore.com/autosize)
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki at snater.com >
 *
 * @option expandWidth {boolean} Whether to horizontally expand/contract the input element.
 *         Default: true
 *
 * @option expandHeight {boolean} Whether to vertically expand/contract the input element.
 *         Default: false
 *
 * @option maxWidth {number} Maximum width the input element may stretch.
 *         Default: 1000
 *
 * @option minWidth {number} Minimum width. If null, the space required by the input element's
 *         placeholder text will be determined automatically (taking placeholder into account).
 *         Default: undefined
 *
 * @option maxHeight {number} Maximum height the input element may stretch. If null, the height is
 *         not constrained to a maximum.
 *         Default: undefined
 *
 * @option minHeight {number} Minimum height. If null, the height is not constrained to a minimum.
 *         Default: undefined
 *
 * @option comfortZone {number} White space behind the input text to prevent resize jitters while
 *         typing. If null, an appropriate amount of space will be calculated automatically.
 *         Default: undefined
 *
 * @option suppressNewLine {boolean} Whether to suppress new-line characters.
 *         Default: false
 *
 * @option eventNamespace {string} Namespace used for the events the plugin attaches handlers to.
 *         Default: 'inputautoexpand'
 *
 * @dependency jquery.event.special.eachchange
 */
( function( $ ) {
	'use strict';

	$.fn.inputautoexpand = function( options ) {
		if( !options ) {
			options = {};
		}

		// Inject default options for missing ones:
		var fullOptions = $.extend( {
			expandWidth: true,
			expandHeight: false,
			maxWidth: 1000,
			minWidth: undefined,
			maxHeight: undefined,
			minHeight: undefined,
			comfortZone: undefined,
			suppressNewLine: false,
			eventNamespace: 'inputautoexpand'
		}, options );

		// Expand input fields:
		this.filter( 'input:text, textarea' ).each( function() {
			var instance = $.data( this, 'inputautoexpand' );

			if( instance ) {
				// AutoExpand initialized already, update options only (will also expand):
				if( options ) {
					instance.options( options );
				}
				instance.expand();

			} else {
				// Initialize new auto expand:
				$.data( this, 'inputautoexpand', new AutoExpandInput( this, fullOptions ) );
			}
		} );

		return this;
	};

	/**
	 * Prototype for auto expanding input elements.
	 * @constructor
	 *
	 * @param {Object} element
	 * @param {Object} options
	 */
	var AutoExpandInput = function( element, options ) {
		this.$input = $( element );
		this._options = options;

		var self = this;

		this._nodeName = element.nodeName;

		this.$input.on( 'eachchange', function( event, oldValue ) {
			if ( self._options.suppressNewLine ) {
				self.$input.val( self.$input.val().replace( /\r?\n/g, '' ) );
			}
			self.expand();
		} );

		initRulers();

		this.expand();

		// Do not show any resize handle for manual resizing:
		this.$input.css( 'resize', 'none' );

		$( window )
		.off( '.' + this._options.eventNamespace )
		.on( 'resize.' + this._options.eventNamespace, function( event ) {
			$( 'input:text, textarea' ).each( function() {
				var instance = $.data( this, 'inputautoexpand' );
				if( instance ) {
					instance.expand();
				}
			} );
		} );
	};

	$.extend( AutoExpandInput.prototype, {
		/**
		 * The input element the auto-expand mechanism is initialized on.
		 * @type {jQuery}
		 */
		$input: null,

		/**
		 * Options.
		 * @type {Object}
		 */
		_options: null,

		/**
		 * Caching the previous input to simply abort expanding when it did not change.
		 * @type {string}
		 */
		_previousVal: null,

		/**
		 * The input element's node name.
		 * @type {string}
		 */
		_nodeName: null,

		/**
		 * Sets the input box's width to fit the box's content.
		 */
		expand: function() {
			var newVal = this.$input.val();

			if( newVal === this._previousVal ) {
				return;
			}

			if( this._options.expandWidth ) {
				this._expandWidth();
			}

			if( this._options.expandHeight && this._nodeName === 'TEXTAREA' ) {
				this._expandHeight();
			}

			this._previousVal = newVal;
		},

		/**
		 * Expands/Contracts the input element's width.
		 */
		_expandWidth: function() {
			copySpaceAffectingStyles( this.$input, $rulerX );

			var minWidth = this._getMinWidth(),
				maxWidth = this._options.maxWidth,
				comfortZone = this._getComfortZone();

			// Since the minimum width may have been calculated dynamically using the placeholder,
			// the minimum width may be greater than the maximum width.
			if( minWidth > maxWidth ) {
				minWidth = maxWidth;
			}

			var valWidth = this._getWidthFor( this.$input.val() ),
				newWidth = valWidth + comfortZone;

			if( newWidth < minWidth ) {
				newWidth = minWidth;
			} else if( newWidth >= maxWidth  ) {
				newWidth = maxWidth;
			}

			this.$input.width( newWidth );
		},

		/**
		 * Expands/Contracts the input element's height.
		 */
		_expandHeight: function() {
			copySpaceAffectingStyles( this.$input, $rulerY );

			var newHeight = this._getHeightFor( this.$input.val() ),
				input = this.$input.get( 0 ),
				minHeight = this._options.minHeight || 0,// keeps at least one single line
				maxHeight = this._options.maxHeight;

			if( maxHeight && newHeight > maxHeight ) {
				input.style.height = maxHeight + 'px';
				input.style.overflow = 'scroll';
			} else {
				if( minHeight && newHeight < minHeight ) {
					input.style.height = minHeight + 'px';
				} else {
					input.style.height = ( !isNaN( newHeight ) ? newHeight : 0 ) + 'px';
				}
				input.style.overflow = 'hidden';
			}
		},

		/**
		 * Calculates the width which would be required for the input field if the given text was
		 * inserted. This does not consider the comfort zone given in the options and doesn't check
		 * for min/max width restraints.
		 *
		 * @param {string} text
		 * @return {number}
		 */
		_getWidthFor: function( text ) {
			$rulerX.html( escaped( text ) );
			return $rulerX.width();
		},

		/**
		 * Returns the minimum width the input element may have assigned.
		 *
		 * @return {number}
		 */
		_getMinWidth: function() {
			if( this._options.minWidth ) {
				return this._options.minWidth;
			}

			// If there is no static minimum width, the placeholder is used to detect the minimum
			// width. Since the placeholder may change, its width is calculated always.
			if( !this.$input.attr( 'placeholder' ) ) {
				return 0;
			}
			// Don't need comfort zone in this case, just some sane space:
			return Math.ceil( this._getWidthFor( this.$input.attr( 'placeholder' ) + ' ' ) );
		},

		/**
		 * Calculates the height the given text would require to not show any scrollbar within the
		 * input element.
		 *
		 * @param {string} text
		 */
		_getHeightFor: function( text ) {
			if( text === '' ) {
				text = ' ';
			}

			var ruler = $rulerY.get( 0 );
			ruler.value = text;

			// Update the width in case the original textarea width has changed:
			var width = this._options.maxWidth;
			if( !this._options.expandWidth || this.$input.width() > this._options.maxWidth ) {
				width = Math.ceil( this.$input.width() ) - 1;
			}

			// Catch miscalculation:
			if( width < 0 ) {
				width = 0;
			}

			ruler.style.width = width + 'px';

			// Set a very high value for scrollTop to be sure the mirror is scrolled all the way to
			// the bottom.
			ruler.scrollTop = 9e4;

			var border = parseInt( this.$input.css( 'borderTopWidth' ), 10 )
				+ parseInt( this.$input.css( 'borderBottomWidth' ), 10 );

			return ( browserSupports0Height )
				? ruler.scrollTop + border
				: ruler.scrollTop + border + ruler.clientHeight;
		},

		/**
		 * Returns the width to add to the input element to prevent jitters when resizing while
		 * typing.
		 *
		 * @return {number}
		 */
		_getComfortZone: function() {
			return ( this._options.comfortZone )
				? this._options.comfortZone
				: Math.ceil( this._getWidthFor( ' ' ) * 2 );
		},

		/**
		 * Sets the plugin's options or gets the options when no parameter is passed in.
		 *
		 * @param {Object} [options]
		 * @return {*|undefined}
		 *
		 * @throws {Error} when trying to set eventNamespace option which should only be set on
		 *         initialization.
		 */
		options: function( options ) {
			if( !options ) {
				return this._options;
			}

			if( options.eventNamespace ) {
				throw new Error( 'Cannot alter eventNamespace after initialization.' );
			}

			$.extend( this._options, options );
		},

		/**
		 * Destroys the plugin instance.
		 */
		destroy: function() {
			$.removeData( this.$input.get( 0 ), 'inputautoexpand' );

			var hasRemainingInstances = false;

			$( 'input' ).each( function() {
				if( $.data( this, 'inputautoexpand' ) ) {
					hasRemainingInstances = true;
					return false;
				}
			} );

			if( !hasRemainingInstances ) {
				$( 'window' ).off( this._options.eventNamespace );
				destroyRulers();
			}
		}
	} );

	/**
	 * Whether the user client is capable of setting the textarea height to 0.
	 * @type {boolean}
	 */
	var browserSupports0Height;

	$( document ).ready( function() {
		browserSupports0Height = supports0Height();
	} );

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

		$t
		.attr( 'style', 'height: 0 !important; width: 0 !important; top:-9999px; left: -9999px;' )
		.text( 'text' )
		.appendTo( $( 'body' ) );

		// Take rounding (height < 1) into account:
		if( $t.height() >= 1 ) {
			support = false;
		}

		$t.remove();

		return support;
	}

	/**
	 * Rulers used for measuring the input content.
	 * @type {jQuery}
	 */
	var $rulerX, $rulerY;

	/**
	 * Initializes the rulers used for measuring the input content.
	 */
	function initRulers() {
		if( !$rulerX ) {
			$rulerX = $( '<div/>' )
				.css( {
					width: 'auto',
					whiteSpace: 'nowrap',
					position: 'absolute',
					top: '-9999px',
					left: '-9999px',
					visibility: 'hidden',
					display: 'none'
				} );
		}

		if( !$rulerX.closest( 'body' ).length ) {
			$rulerX.appendTo( 'body' );
		}

		if( !$rulerY ) {
			$rulerY = $( '<textarea style="minHeight: 0!important; height: 0!important;"/>' )
				.attr( 'tabindex', '-1' )
				.css( {
					position: 'absolute',
					top: '-9999px',
					left: '-9999px',
					right: 'auto',
					bottom: 'auto',
					wordWrap: 'break-word'
				} );
		}

		if( !$rulerY.closest( 'body' ).length ) {
			$rulerY.appendTo( 'body' );
		}
	}

	/**
	 * Destroys the rulers.
	 */
	function destroyRulers() {
		if( $rulerX ) {
			$rulerX.remove();
			$rulerX = null;
		}
		if( $rulerY ) {
			$rulerY.remove();
			$rulerY.remove();
		}
	}

	/**
	 * Copy styles that affect spacing from one element to another.
	 *
	 * @param {jQuery} $from
	 * @param {jQuery} $to
	 */
	function copySpaceAffectingStyles( $from, $to ) {
		var stylesToCopy = [
			'fontFamily',
			'fontSize',
			'fontWeight',
			'fontStyle',
			'letterSpacing',
			'lineHeight',
			'textTransform',
			'wordSpacing',
			'textIndent',
			'overflowY'
		];

		for( var i = 0; i < stylesToCopy.length; i++ ) {
			$to.css( stylesToCopy[i], $from.css( stylesToCopy[i] ) );
		}

		// styles not being influenced by copying styles
		$to.css( {
			overflow: 'hidden',
			overflowY: 'hidden',
			wordWrap: 'break-word'
		} );
	}

	/**
	 * Escapes HTML entities.
	 *
	 * @param {string} text
	 * @return {string}
	 */
	function escaped( text ) {
		return $( '<div/>' ).text( text ).html();
	}

}( jQuery ) );

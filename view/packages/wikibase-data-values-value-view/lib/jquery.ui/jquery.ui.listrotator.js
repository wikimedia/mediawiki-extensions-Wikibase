/**
 * List rotator widget
 *
 * The list rotator may be used to rotate through a list of values. The previous and next value
 * according to the currently selected value are displayed as links next to the current value. In
 * addition, clicking the current value reveals a drop-down list to directly select a value from the
 * list values.
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @option {Object[]} values (REQUIRED) Array of objects containing the values to rotate.
 *         Single object structure:
 *         { value: <actual value (being returned on value())>, label: <the value's label> }
 *
 * @option {Object} [menu] Options for the jQuery.menu widget used as drop-down menu:
 *         {Object} [menu.position] Default object passed to jQuery.ui.position when positioning the
 *         menu. Positions will be flipped if isRtl option returns "true".
 *
 * @option {Object} [animation] Object containing parameters used for the rotation animation.
 *         {string[]} [margins] Defines how far the sections should be shifted when animating the
 *         rotation. First value when shifting to the left and vice versa. Values will be flipped in
 *         rtl context.
 *         Default: ['-15px', '15px']
 *         {number} [duration] Defines the animation's duration in milliseconds.
 *         Default: 150
 *
 * @option {boolean} [deferInit] Whether to defer initializing the section widths until initWidths()
 *         is called "manually".
 *         Default: false
 *
 * @option {boolean|Function} [isRTL] Whether widget is used in an RTL context.
 *         Default: function() { return $( 'body' ).hasClass( 'rtl' ); }
 *
 * @event auto: Triggered when "auto" options is selected.
 *        (1) {jQuery.Event}
 *
 * @event selected: Triggered when a specific value is selected.
 *        (1) {jQuery.Event}
 *        (2) {*} Value as specified in the "values" option.
 *
 * @dependency jQuery.Widget
 * @dependency jQuery.ui.menu
 * @dependency jQuery.ui.position
 */
( function( $ ) {
	'use strict';

	/**
	 * Whether loaded in MediaWiki context.
	 * @type {boolean}
	 */
	var IS_MW_CONTEXT = ( typeof mediaWiki !== 'undefined' && mediaWiki.msg );

	/**
	 * Whether actual listrotator resource loader module is loaded.
	 * @type {boolean}
	 */
	var IS_MODULE_LOADED = (
		IS_MW_CONTEXT
			&& $.inArray( 'jquery.ui.listrotator', mediaWiki.loader.getModuleNames() ) !== -1
		);

	/**
	 * Returns a message from the MediaWiki context if the listrotator module has been loaded.
	 * If it has not been loaded, the corresponding string defined in the options will be returned.
	 *
	 * @param {String} msgKey
	 * @param {String} string
	 * @return {String}
	 */
	function mwMsgOrString( msgKey, string ) {
		return ( IS_MODULE_LOADED ) ? mediaWiki.msg( msgKey ) : string;
	}

	/**
	 * Measures the maximum width of a container according to a list of strings. The width is
	 * determined by the widest string.
	 *
	 * @param {jQuery} $container
	 * @param {string[]} strings
	 * @returns {number} The container's maximum width in pixel
	 */
	function measureMaximumStringWidths( $container, strings ) {
		var widths = [];
		$.each( strings, function( i, string ) {
			$container.empty().text( string );
			widths.push( $container.width() );
		} );
		$container.empty();
		return widths;
	}

	$.widget( 'ui.listrotator', {
		/**
		 * Additional options
		 * @type {Object}
		 */
		options: {
			values: [],
			menu: {
				position: {
					my: 'left top',
					at: 'left bottom',
					collision: 'none'
				}
			},
			animation: {
				margins: ['-15px', '15px'],
				duration: 150
			},
			deferInit: false,
			messages: {
				'auto': mwMsgOrString( 'valueview-listrotator-auto', 'auto' )
			},
			isRtl: function() {
				return $( 'body' ).hasClass( 'rtl' );
			}
		},

		/**
		 * Node of the selectable "auto" option.
		 * @type {jQuery}
		 */
		$auto: null,

		/**
		 * Node of the previous list item section.
		 * @type {jQuery}
		 */
		$prev: null,

		/**
		 * Node of the current list item section.
		 * @type {jQuery}
		 */
		$curr: null,

		/**
		 * Node of the next list item section.
		 * @type {jQuery}
		 */
		$next: null,

		/**
		 * Node of the menu opening when clicking on the "current" section.
		 * @type {jQuery}
		 */
		$menu: null,

		/**
		 * Temporarily caching the value the rotator is rotating to while the animation is being
		 * performed.
		 * @type {*}
		 */
		_rotatingTo: null,

		/**
		 * @see $.ui.Widget._create
		 */
		_create: function() {
			var self = this,
				iconClasses = ['ui-icon ui-icon-triangle-1-w', 'ui-icon ui-icon-triangle-1-e'];

			// Flip triangle arrows in rtl context:
			if ( this._isRtl() ) {
				iconClasses.reverse();
			}

			if ( this.options.values.length === 0 ) {
				throw new Error( 'List of values required to initialize list rotator.' );
			}

			this.element.addClass( this.widgetBaseClass + ' ui-widget-content' );

			// Construct "auto" link:
			this.$auto = this._createSection( 'auto', function( event ) {
				if( self.autoActive() ) {
					return;
				}
				self.activate( self.$auto );
				self._trigger( 'auto' );
			} )
			.addClass( 'ui-state-active' );
			this.$auto.children( 'span' ).text( this.options.messages.auto );

			// Construct the basic sections:
			this.$curr = this._createSection( 'curr', function( event ) {
				if ( !self.$menu.is( ':visible' ) ) {
					self._showMenu();
				} else {
					self._hideMenu();
				}
			} )
			.append( $( '<span/>' ).addClass( 'ui-icon ui-icon-triangle-1-s' ) );

			this.$prev = this._createSection( 'prev', function( event ) {
				self.prev();
			} )
			.append( $( '<span/>' ).addClass( iconClasses[0] ) );

			this.$next = this._createSection( 'next', function( event ) {
				self.next();
			} )
			.append( $( '<span/>' ).addClass( iconClasses[1] ) );

			if( this.$auto ) {
				this.element.append( this.$auto );
			}
			this.element.append( this.$prev ).append( this.$curr ).append( this.$next );

			// Construct and initialize menu widget:
			this._createMenu();

			// Attach event to html node to detect click outside of the menu closing the menu:
			$( 'html' )
			.off( '.' + this.widgetName )
			.on( 'click.' + this.widgetName, function( event ) {
				$( ':' + self.widgetBaseClass ).each( function( i, node ) {
					var $target = $( event.target ),
						listrotator = $( node ).data( 'listrotator' );

					// Hide the menu if it is neither the "current" node nor the menu's node that
					// has been clicked.
					if( !$target.closest( listrotator.$curr.add( listrotator.$menu ) ).length ) {
						listrotator.$menu.hide();
					}

				} );
			} );

			// Focus on first element:
			this.value( this.options.values[0].value );

			if( !this.options.deferInit ) {
				this.initWidths();
			}

		},

		/**
		 * @see $.Widget.destroy
		 */
		destroy: function() {
			var menu = this.$menu.data( 'menu' );
			if( menu ) {
				menu.destroy();
			}

			this.$menu.remove();
			this.$auto.remove();
			this.$curr.remove();
			this.$prev.remove();
			this.$next.remove();

			this.element.removeClass( this.widgetBaseClass + ' ui-widget-content' );

			$.Widget.prototype.destroy.call( this );

			// Remove event attached to the html node if no instances of the widget exist anymore:
			if ( $( ':' + this.widgetBaseClass ).length === 0 ) {
				$( 'html' ).off( '.' + this.widgetBaseClass );
			}
		},

		/**
		 * Init the section widths.
		 */
		initWidths: function() {
			// Determine the maximum width a label may have and apply that width to each section:
			var currentLabel = this.$curr.children( '.' + this.widgetBaseClass + '-label' ).text(),
				labels = [],
				stringWidths = [],
				currMaxWidth = 0,
				prevMaxWidth = 0,
				nextMaxWidth = 0;

			$.each( this.options.values, function( i, v ) {
				labels.push( v.label );
			} );

			stringWidths = measureMaximumStringWidths(
				this.$curr.children( '.' + this.widgetBaseClass + '-label' ),
				labels
			);
			$.each( stringWidths, function( i, width ) {
				if( width > currMaxWidth ) {
					currMaxWidth = width;
				}
				if( i < stringWidths.length && width > prevMaxWidth ) {
					prevMaxWidth = width;
				}
				if( i > 0 && width > nextMaxWidth ) {
					nextMaxWidth = width;
				}
			} );

			this.$curr.children( '.' + this.widgetBaseClass + '-label' ).width( currMaxWidth );
			// The "previous" section will not be filled with the last string while the "next"
			// section will never be filled with the first string.
			this.$prev.children( '.' + this.widgetBaseClass + '-label' ).width( prevMaxWidth );
			this.$next.children( '.' + this.widgetBaseClass + '-label' ).width( nextMaxWidth );

			// Make menu width comply to the "current" section:
			var menuSpacing = this.$menu.outerWidth() - this.$menu.width();
			this.$menu.width( this.$curr.outerWidth() - menuSpacing );

			// Reset "current" section's label:
			this.$curr.children( '.' + this.widgetBaseClass + '-label' ).text( currentLabel );
		},

		/**
		 * Creates a widget section.
		 *
		 * @param {string} classSuffix
		 * @param {Function} clickCallback
		 * @return {jQuery}
		 */
		_createSection: function( classSuffix, clickCallback ) {
			return $( '<a/>' )
			.attr( 'href', 'javascript:void(0);' )
			.addClass( this.widgetBaseClass + '-' + classSuffix )
			.on( 'click.' + this.widgetBaseClass, function( event ) {
				if( !$( this ).hasClass( 'ui-state-disabled' ) ) {
					clickCallback( event );
				}
			} )
			.append( $( '<span/>' ).addClass( this.widgetBaseClass + '-label ui-state-default' ) );
		},

		/**
		 * Create the drop-down menu assigned to the "current" section.
		 */
		_createMenu: function() {
			var self = this;

			this.$menu = $( '<ul/>' )
			.addClass( this.widgetBaseClass + '-menu' )
			.appendTo( $( 'body' ) ).hide();

			$.each( this.options.values, function( i, v ) {
				self.$menu.append(
					$( '<li/>' )
					.append(
						$( '<a/>' )
						.attr( 'href', 'javascript:void(0);')
						.text( v.label )
						.on( 'click', function( event ) {
							event.stopPropagation();
							self._trigger( 'selected', null, [ self.value( v.value ) ] );
							self.$menu.hide();
						} )
					)
					.data( 'value', v.value )
				);
			} );

			this.$menu.menu();
		},

		/**
		 * Sets/Gets the widget's value. Setting the value involves setting the rotator to the
		 * specified value without any animation.
		 *
		 * TODO: Change behavior: value as setter should return "this" for allowing chaining calls
		 *  to the widget.
		 *
		 * @param [value] The value to assign. (Has to match a value actually existing in the
		 *        widget's options.)
		 * @return {string} Current value.
		 */
		value: function( value ) {
			// Get the current value:
			if ( value === undefined || value === this.$curr.data( 'value' ) ) {
				return this.$curr.data( 'value' );
			}

			var values = this.options.values,
				index = 0;

			this.$prev.add( this.$curr ).add( this.$next )
			.children( '.' + this.widgetBaseClass + '-label' ).empty();

			// Retrieve the index of the new value within the list of predefined values:
			$.each( values, function( i, v ) {
				if ( value === v.value ) {
					index = i;
					return false;
				}
			} );

			// Re-construct each section:
			this.$curr
			.data( 'value', values[index].value )
			.children( '.' + this.widgetBaseClass + '-label' )
			.text( values[index].label );

			if ( index > 0 ) {
				this.$prev
				.data( 'value', values[index - 1].value )
				.children( '.' + this.widgetBaseClass + '-label' )
				.text( values[index - 1].label );
			}

			if ( index < values.length - 1 ) {
				this.$next
				.data( 'value', values[index + 1].value )
				.children( '.' + this.widgetBaseClass + '-label' )
				.text( values[index + 1].label );
			}

			// Hide "previous"/"$next" section when the new value is at the end of the list the
			// predefined values:
			this.$prev.css( 'visibility', ( index === 0 ) ? 'hidden' : 'visible' );
			this.$next.css( 'visibility', ( index === values.length - 1 ) ? 'hidden' : 'visible' );

			// Alter menu item states:
			this.$menu.children( 'li' ).each( function( i, li ) {
				var $li = $( li );
				$li.removeClass( 'ui-state-active' );
				if( $li.data( 'value' ) === value ) {
					$li.addClass( 'ui-state-active' );
				}
			} );

			return value;
		},

		/**
		 * Sets a new value rotating to the new value.
		 *
		 * @param {*} newValue
		 */
		_setValue: function( newValue ) {
			var self = this;

			if( this.$curr.data( 'value' ) === newValue ) {
				// Value is set already.
				return;
			}

			this.element.one( this.widgetEventPrefix + 'selected', function( event, newValue ) {
				self.activate();
			} );

			this.rotate( newValue );
		},

		/**
		 * Rotates the widget to the next value.
		 */
		next: function() {
			this._setValue( this.$next.data( 'value' ) );
		},

		/**
		 * Rotates the widget to the previous value.
		 */
		prev: function() {
			this._setValue( this.$prev.data( 'value' ) );
		},

		/**
		 * Performs the rotation of the widget.
		 *
		 * @param {string} newValue
		 */
		rotate: function( newValue ) {
			if(
				newValue === this._rotatingTo
				|| !this._rotatingTo && newValue === this.$curr.data( 'value' )
			) {
				// Rotation is to the given target is in progress or has been performed already.
				return;
			}

			var self = this,
				margins = $.merge( [], this.options.animation.margins ),
				s = '.' + this.widgetBaseClass + '-label';

			// Nodes that shall be animated:
			var $nodes = this.$prev.children( s )
				.add( this.$curr.children( s ) )
				.add( this.$next.children( s ) );

			// Set the rotation target:
			this._rotatingTo = newValue;

			// Figure out whether rotating to the right or to the left:
			var beforeCurrent = true;
			$.each( this.options.values, function( i, v ) {
				if( v.value === newValue ) {
					return false;
				}
				if( v.value === self.$curr.data( 'value' ) ) {
					beforeCurrent = false;
					return false;
				}
			} );

			if( beforeCurrent ) {
				margins.reverse();
			}

			if ( this._isRtl() ) {
				margins.reverse();
			}

			$nodes.animate(
				{
					marginLeft: margins[0],
					marginRight: margins[1],
					opacity: 0
				}, {
					done: function() {
						// Reset margins an opacity used for the animation effect:
						$nodes.css( {
							marginLeft: '0',
							marginRight: '0',
							opacity: 1
						} );

						// Rotation target changed in the meantime, just abort selecting:
						if( self._rotatingTo !== newValue ) {
							return;
						}

						self._trigger( 'selected', null, [ self.value( newValue ) ] );

						self._rotatingTo = null;
					},
					duration: this.options.animation.duration
				}
			);
		},

		/**
		 * Returns whether in RTL context.
		 *
		 * @return {boolean}
		 */
		_isRtl: function() {
			return ( $.isFunction( this.options.isRtl ) )
				? this.options.isRtl()
				: this.options.isRtl;
		},

		/**
		 * Activates the widget.
		 *
		 * @param {jQuery} [$section] Section to activate. "Current" section by default.
		 */
		activate: function( $section ) {
			this.$curr.add( this.$auto ).removeClass( 'ui-state-hover ui-state-active' );

			if( $section === undefined ) {
				$section = this.$curr;
			}

			$section.addClass( 'ui-state-active' );
		},

		/**
		 * De-activates the widget.
		 */
		deactivate: function() {
			this.$curr.add( this.$auto ).removeClass( 'ui-state-active' );
		},

		/**
		 * Returns whether the listrotator is currently set to "auto", meaning that the value
		 * returned by value() has not been chosen by the user explicitly.
		 *
		 * @return {boolean}
		 */
		autoActive: function() {
			return this.$auto.hasClass( 'ui-state-active' );
		},

		/**
		 * Shows the drop-down menu.
		 */
		_showMenu: function() {
			this.$menu.slideDown( this.options.animation.duration );

			function flip( string ) {
				var segments = $.map( string.split( ' ' ), function( segment ) {
					return ( segment.indexOf( 'left' ) !== -1 )
						? segment.replace( 'left', 'right' )
						: segment.replace( 'right', 'left' );
				} );
				return segments.join( ' ' );
			}

			this.$menu.position( $.extend( {
				of: this.$curr
			}, {
				my: flip( this.options.menu.position.my ),
				at: flip( this.options.menu.position.at )
			} ) );

			this.activate();
		},

		/**
		 * Hides the drop-down menu.
		 */
		_hideMenu: function() {
			this.$menu.slideUp( this.options.animation.duration );
			this.activate();
		},

		/**
		 * Disables the widget.
		 */
		disable: function() {
			this.$prev.add( this.$curr ).add( this.$next )
			.addClass( 'ui-state-disabled' );
		},

		/**
		 * Enables the widget.
		 */
		enable: function() {
			this.$prev.add( this.$curr ).add( this.$next )
			.removeClass( 'ui-state-disabled' );
		}

	} );

} )( jQuery );

( function () {
	'use strict';

	require( './jquery.ui.ooMenu.js' );

	/**
	 * Flips a complete position specification to be used by jQuery.ui.position (1.8).
	 *
	 * @ignore
	 *
	 * @param {Object} position
	 * @return {Object}
	 */
	function flipPosition( position ) {
		function flipOrientation( orientation ) {
			if ( /right/i.test( orientation ) ) {
				return orientation.replace( /right/i, 'left' );
			} else {
				return orientation.replace( /left/i, 'right' );
			}
		}

		function flipHorizontalOffset( offset ) {
			var offsets = offset.split( ' ' ),
				hOffset = parseInt( offsets[ 0 ], 10 );

			hOffset = ( hOffset <= 0 ) ? Math.abs( hOffset ) : hOffset * -1;
			return hOffset + ' ' + offsets[ 1 ];
		}

		position.my = flipOrientation( position.my );
		position.at = flipOrientation( position.at );

		if ( position.offset ) {
			position.offset = flipHorizontalOffset( position.offset );
		}

		return position;
	}

	/**
	 * Enhances an input box by retrieving a list of suggestions that are displayed in a list below the
	 * input box.
	 * (uses `jQuery.ui.ooMenu`, `jQuery.ui.position`)
	 *
	 *     @example
	 *     // Creates a simple suggester using an array as result set.
	 *     $( 'input' ).suggester( { source: ['a', 'b', 'c'] } );
	 *
	 *     // Creates an auto-completion input element fetching suggestions via AJAX.
	 *     $( 'input' ).suggester( {
	 *         source: function( term ) {
	 *             var deferred = $.Deferred();
	 *
	 *             $.ajax( {
	 *                 url: 'https://commons.wikimedia.org/w/api.php',
	 *                 dataType: 'jsonp',
	 *                 data: {
	 *                 search: term,
	 *                 action: 'opensearch',
	 *                 namespace: 6
	 *             }, timeout: 8000 } )
	 *             .done( function( response ) {
	 *                 deferred.resolve( response[1], response[0] );
	 *             } )
	 *             .fail( function( jqXHR, textStatus ) {
	 *                 deferred.reject( textStatus );
	 *             } );
	 *
	 *             return deferred.promise();
	 *         }
	 *     } );
	 *
	 * @class jQuery.ui.suggester
	 * @extends jQuery.Widget
	 * @uses jQuery.ui
	 * @license GNU GPL v2+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 *
	 * @param {Object} options
	 * @param {string[]|Function} options.source
	 *        An array of strings that shall be used to provide suggestions. Alternatively, a function
	 *        may be provided
	 *        Parameters:
	 *
	 * - {string} Search term
	 *
	 * Expected return values:
	 *
	 * - {Object} jQuery promise
	 *
	 * Resolved parameters:
	 *
	 * - {string[]} Suggestions
	 *
	 * - {string} (optional) Search term corresponding to the suggestions. This allows checking whether
	 *   the response belongs to the most current request.
	 *
	 * Rejected parameters:
	 *
	 * - {string} Plain text or HTML error message.
	 *
	 * @param {number} [options.minTermLength=1]
	 *        Minimum number of characters to trigger a search with.
	 * @param {number} [options.delay=300]
	 *        Delay in milliseconds of the request querying for suggestions.
	 * @param {jQuery.ui.ooMenu|null} [menu=null]
	 *        A pre-initialized menu instance featuring one or more custom list item may be provided.
	 *        This should be the preferred way to define custom items.
	 * @param {Object} [position=Object]
	 *        Object to be evaluated by `jQuery.ui.position` to set the suggestion list's position.
	 *        In RTL context, the specified value is flipped automatically.
	 *        Default: (position suggestion list's top left corner at input box's bottom left corner)
	 * @param {jQuery|null} [confineMinWidthTo]
	 *        The suggestion list's width shall not be smaller than the width of the referenced
	 *        element. If `undefined`, the minimum width will be the width of the element the suggester
	 *        is initialized on. Specifying `null` or `undefined` will prevent applying a minimum
	 *        width.
	 */
	/**
	 * @event open
	 * Triggered when the list of suggestions is opened.
	 * @param {jQuery.Event} event
	 */
	/**
	 * @event close
	 * Triggered when the list of suggestions is closed.
	 * @param {jQuery.Event} event
	 */
	/**
	 * @event change
	 * Triggered when the suggester's value has changed.
	 * @param {jQuery.Event} event
	 */
	/**
	 * @event error
	 * Triggered whenever an error occurred while gathering suggestions. This may happen only when using
	 * a function as source. The {string} parameter is forwarded from the rejected promise returned by
	 * the source function.
	 * @param {jQuery.Event} event
	 * @param {string} message
	 */
	$.widget( 'ui.suggester', {

		/**
		 * @see jQuery.Widget.options
		 * @protected
		 * @readonly
		 */
		options: {
			source: null,
			minTermLength: 1,
			delay: 300,
			menu: null,
			position: {
				my: 'left top',
				at: 'left bottom',
				collision: 'none'
			},
			confineMinWidthTo: undefined
		},

		/**
		 * Counter for the number of pending requests.
		 *
		 * @property {number}
		 * @protected
		 */
		_pending: null,

		/**
		 * Current search term.
		 *
		 * @property {string}
		 * @protected
		 */
		_term: null,

		/**
		 * Caches whether searching is in progress by either storing the ID of the timer used to delay
		 * the actual search request or by storing a boolean "true" while the actual search request is
		 * in progress.
		 *
		 * @property {number|boolean} [_searching=false]
		 * @protected
		 */
		_searching: false,

		/**
		 * @see jQuery.Widget._create
		 * @protected
		 */
		_create: function () {
			var self = this;

			this._pending = 0;
			this._term = this.element.val();

			this.element
		.addClass( 'ui-suggester-input' )
		.on( 'blur.' + this.widgetName, function () {
			if ( !self.options.menu.element.is( ':focus' ) ) {
				self._close();
			}
		} );

			if ( !( this.options.menu instanceof $.ui.ooMenu ) ) {
				var $menu = $( '<ul>' ).ooMenu();
				this.options.menu = $menu.data( 'ooMenu' );
			}

			this.options.menu = this._initMenu( this.options.menu );

			this._attachInputEventHandlers();
			this._attachWindowEventHandlers();
		},

		/**
		 * @see jQuery.Widget.destroy
		 */
		destroy: function () {
			this._clearTimeout();

			var menu = this.option( 'menu' );
			menu.destroy();
			menu.element.remove();
			this.option( 'menu', null );

			// About to remove the last suggester instance on the page:
			if ( $( ':' + this.widgetBaseClass ).length === 1 ) {
				$( window ).off( '.' + this.widgetBaseClass );
			}

			this.element.removeClass( 'ui-suggester-input ui-suggester-loading ui-suggester-error' );

			$.Widget.prototype.destroy.call( this );
		},

		/**
		 * @param key
		 * @param value
		 * @see jQuery.Widget._setOption
		 * @protected
		 */
		_setOption: function ( key, value ) {
			if ( key === 'menu' ) {
				this.options.menu.destroy();
				this.options.menu.element.remove();
			}

			var response = $.Widget.prototype._setOption.apply( this, arguments );

			if ( key === 'menu' && value instanceof $.ui.ooMenu ) {
				this.options.menu = this._initMenu( value );
			}

			if ( key === 'disabled' ) {
				if ( value ) {
					this._close();
				}
				this.element.prop( 'disabled', value );
			}

			return response;
		},

		/**
		 * Renders the menu and attaches the menu's event handlers.
		 *
		 * @protected
		 *
		 * @param {jQuery.ui.ooMenu} ooMenu
		 * @return {jQuery.ui.ooMenu}
		 */
		_initMenu: function ( ooMenu ) {
			var self = this;

			ooMenu.element
		.addClass( 'ui-suggester-list' )
		.hide()
		.appendTo( 'body' );

			$( ooMenu )
		.on( 'selected.suggester', function ( event, item ) {
			if ( item instanceof $.ui.ooMenu.Item && !( item instanceof $.ui.ooMenu.CustomItem ) ) {
				self._term = item.getValue();
				self.element.val( item.getValue() );
				self._close();
				self._trigger( 'change' );

				if ( !event.originalEvent || !/^key/.test( event.originalEvent.type ) ) {
					setTimeout( function () {
						// Run refocusing out of the execution chain to allow redrawing in IE.
						self.element.trigger( 'focus' );
					}, 0 );
				}
			}
		} );

			return ooMenu;
		},

		/**
		 * Attaches input event handlers to the input element.
		 *
		 * @protected
		 */
		_attachInputEventHandlers: function () {
			var self = this,
				suppressKeyPress = false;

			this.element
		.on( 'click.suggester', function ( event ) {
			if ( !self.isSearching() ) {
				self._updateMenuVisibility();
			}
		} )
		.on( 'keydown.suggester', function ( event ) {
			var isDisabled = self.element.hasClass( 'ui-state-disabled' );

			if ( isDisabled || self.element.prop( 'readOnly' ) ) {
				return;
			}

			self.element.removeClass( 'ui-suggester-error' );

			suppressKeyPress = false;

			var keyCode = $.ui.keyCode;

			switch ( event.keyCode ) {
				case keyCode.UP:
					self._keyMove( 'previous', event );
					break;

				case keyCode.DOWN:
					self._keyMove( 'next', event );
					break;

				case keyCode.ENTER:
				case keyCode.NUMPAD_ENTER:
					if ( self.options.menu.getActiveItem() ) {
						// Prevent form submission and select currently active item.
						event.preventDefault();
						event.stopPropagation();
						suppressKeyPress = true;
						self.options.menu.select( event );
					}
					break;

				case keyCode.TAB:
					if ( !self.options.menu.getActiveItem() ) {
						self._close();
						return;
					}
					self.options.menu.select( event );
					break;

				case keyCode.ESCAPE:
					self.element.val( self._term );
					// eslint-disable-next-line no-jquery/no-sizzle
					if ( self.options.menu.element.is( ':visible' ) ) {
						event.stopPropagation();
						self._close();
					}
					break;

				default:
					if ( self.element.val() === ''
						&& (
							event.keyCode === keyCode.BACKSPACE
							|| event.keyCode === keyCode.DELETE
						)
					) {
						break;
					}

					self._triggerSearch();

					break;
			}

			self._trigger( 'change' );
		} )
		.on( 'keypress.suggester', function ( event ) {
			if ( suppressKeyPress ) {
				suppressKeyPress = false;
				event.preventDefault();
			}
		} );
		},

		/**
		 * Attaches event listeners to the `window` object.
		 *
		 * @protected
		 */
		_attachWindowEventHandlers: function () {
			var self = this;

			$( window )
		.off( '.' + this.widgetBaseClass )
		.on( 'resize.' + this.widgetBaseClass, function () {
			$( ':' + self.widgetBaseClass ).each( function ( i, node ) {
				var suggester = $( node ).data( self.widgetName );
				suggester.repositionMenu();
				suggester.options.menu.scale();
			} );
		} )
		.on( 'click.' + this.widgetBaseClass, function ( event ) {
			var $target = $( event.target );
			$( ':' + self.widgetBaseClass ).each( function ( i, node ) {
				var suggester = $( node ).data( self.widgetName );
				// Close suggester if not clicked on suggester or corresponding list:
				if ( $target.closest( suggester.element ).length === 0
					&& $target.closest( suggester.options.menu.element ).length === 0
				) {
					suggester._close();
				}
			} );
		} );
		},

		/**
		 * @private
		 */
		_triggerSearch: function () {
			var self = this;

			this._clearTimeout();

			this._searching = setTimeout( function () {
			// Only search if the value has changed:
				if ( self._term !== self.element.val() ) {
					self.search()
				.done( function () {
					// Widget might have been destroyed in the meantime.
					if ( self.element.data( self.widgetName ) ) {
						self._trigger( 'change' );
					}
				} );
				}
			}, this.options.delay );
		},

		/**
		 * Returns whether searching is in progress.
		 *
		 * @return {boolean}
		 */
		isSearching: function () {
			return this._searching !== false;
		},

		/**
		 * Handles moving through the list of suggestions using arrow keys.
		 *
		 * @protected
		 *
		 * @param {string} direction (either "previous" or "next")
		 * @param {jQuery.Event} event
		 */
		_keyMove: function ( direction, event ) {
		// Prevent moving cursor to beginning/end of the text field in some browsers:
			event.preventDefault();

			// eslint-disable-next-line no-jquery/no-sizzle
			if ( !this.options.menu.element.is( ':visible' ) ) {
				this.search();
				return;
			}

			var allItems = $.merge( [], this.options.menu.option( 'items' ) );
			$.merge( allItems, this.options.menu.option( 'customItems' ) );

			if ( allItems.length > 0 ) {
				this._move( direction, this.options.menu.getActiveItem(), allItems );
			}
		},

		/**
		 * Shifts the suggestions menu focus by one item.
		 *
		 * @protected
		 *
		 * @param {string} direction
		 * @param {jQuery.ui.ooMenu.Item} activeItem
		 * @param {jQuery.ui.ooMenu.Item[]} allItems
		 */
		_move: function ( direction, activeItem, allItems ) {
			var self = this,
				isFirst = activeItem === allItems[ 0 ],
				isLast = activeItem === allItems[ allItems.length - 1 ];

			if ( isFirst && direction === 'previous' || isLast && direction === 'next' ) {
				this._moveOffEdge( direction );
			} else {
				$( this.options.menu ).one( 'focus.suggester', function ( event, item ) {
					var isCustomMenuItem = item instanceof $.ui.ooMenu.CustomItem;

					if ( item instanceof $.ui.ooMenu.Item && !isCustomMenuItem ) {
						self.element.val( item.getValue() );
					} else if ( isCustomMenuItem ) {
						self.element.val( self._term );
					}
					self._trigger( 'change' );
				} );
				this.options.menu[ direction ]();
			}
		},

		/**
		 * Handler called when the suggestion menu focus is to be shifted off the end of the list.
		 *
		 * @protected
		 *
		 * @param {string} direction
		 */
		_moveOffEdge: function ( direction ) {
			this.element.val( this._term );
			this.options.menu.deactivate();
		},

		/**
		 * Performs a search on the current input.
		 *
		 * @return {Object} jQuery.Promise
		 * @return {Function} return.done
		 * @return {string[]} return.done.suggestions
		 * @return {Function} return.fail
		 * @return {string} return.fail.message
		 */
		search: function () {
			var self = this,
				deferred = $.Deferred();

			this._clearTimeout();
			this._searching = true;

			this._term = this.element.val();

			if ( this._term.length < this.options.minTermLength ) {
				this._close();
				return deferred.resolve( [], this._term ).promise();
			}

			this.element.addClass( 'ui-suggester-loading' );
			this._pending++;

			return this._getSuggestions( this._term )
		.done( function ( suggestions, requestTerm ) {
			self._searching = false;

			if ( typeof requestTerm === 'string' && requestTerm !== self._term ) {
				// Skip request since it does not correspond to the current search term.
				return;
			}
			if ( self.options.menu ) {
				// Suggester (including the menu) might have been destroyed in the meantime.
				self._updateMenu( suggestions, requestTerm );
			}
		} )
		.fail( function ( message ) {
			self.element.addClass( 'ui-suggester-error' );
			self._trigger( 'error', null, [ message ] );
		} )
		.always( function () {
			if ( --self._pending === 0 ) {
				self.element.removeClass( 'ui-suggester-loading' );
			}
		} );
		},

		/**
		 * Clears the timeout used to delay searching if there is an active timer.
		 *
		 * @protected
		 */
		_clearTimeout: function () {
			if ( typeof this._searching !== 'boolean' ) {
				clearTimeout( this._searching );
			}
		},

		/**
		 * Updates the menu.
		 *
		 * @protected
		 *
		 * @param {string[]} suggestions
		 * @param {string} requestTerm
		 */
		_updateMenu: function ( suggestions, requestTerm ) {
			this._updateMenuItems( suggestions, requestTerm );
			this._updateMenuVisibility();
		},

		/**
		 * Updates the suggestion menu with the received suggestions.
		 *
		 * @protected
		 *
		 * @param {string[]} suggestions
		 * @param {string} requestTerm
		 */
		_updateMenuItems: function ( suggestions, requestTerm ) {
			var menuItems = [];

			for ( var i = 0; i < suggestions.length; i++ ) {
				menuItems.push( this._createMenuItemFromSuggestion( suggestions[ i ], requestTerm ) );
			}

			this.options.menu.option( 'items', menuItems );
		},

		/**
		 * Updates the menu's visibility.
		 *
		 * @protected
		 */
		_updateMenuVisibility: function () {
			if ( !this.options.menu.hasVisibleItems( true ) ) {
				this._close();
			} else {
				this._open();
			}
		},

		/**
		 * Instantiates a menu item instance from a suggestion.
		 *
		 * @protected
		 *
		 * @param {string} suggestion
		 * @param {string} requestTerm
		 * @return {jQuery.ui.ooMenu.Item}
		 */
		_createMenuItemFromSuggestion: function ( suggestion, requestTerm ) {
			return new $.ui.ooMenu.Item( suggestion );
		},

		/**
		 * Retrieves the suggestions for a specific search term.
		 *
		 * @protected
		 *
		 * @param {string} term
		 * @return {Object} jQuery.Promise
		 * @return {Function} return.done
		 * @return {string[]} return.done.suggestions
		 * @return {string} return.done.requestTerm
		 * @return {Function} return.fail
		 * @return {string} return.fail.message
		 */
		_getSuggestions: function ( term ) {
			if ( typeof this.options.source === 'function' ) {
				return this.options.source( term );
			}

			return this._getSuggestionsFromArray( term, this.options.source );
		},

		/**
		 * Filters an array using a specific search term.
		 *
		 * @protected
		 *
		 * @param {string} term
		 * @param {string[]} source
		 * @return {Object} jQuery.Promise
		 * @return {Function} return.done
		 * @return {string[]} return.done.suggestions
		 * @return {string} return.done.requestTerm
		 * @return {Function} return.fail
		 * @return {string} return.fail.message
		 */
		_getSuggestionsFromArray: function ( term, source ) {
			var deferred = $.Deferred();

			var matcher = new RegExp( this._escapeRegex( term ), 'i' );

			// eslint-disable-next-line no-jquery/no-grep
			deferred.resolve( $.grep( source, function ( item ) {
				return matcher.test( item );
			} ), term );

			return deferred.promise();
		},

		/**
		 * Escapes a string to be used in a regular expression.
		 *
		 * @protected
		 *
		 * @param {string} value
		 * @return {string}
		 */
		_escapeRegex: function ( value ) {
			return value.replace( /[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&' );
		},

		/**
		 * Shows the suggester menu.
		 *
		 * @protected
		 */
		_open: function () {
			// eslint-disable-next-line no-jquery/no-sizzle
			if ( this.options.menu.element.is( ':visible' ) ) {
				return;
			}

			this.options.menu.element.show();
			this.repositionMenu();

			this._trigger( 'open' );
		},

		/**
		 * Hides the suggester menu.
		 *
		 * @protected
		 */
		_close: function () {
			// eslint-disable-next-line no-jquery/no-sizzle
			if ( !this.options.menu.element.is( ':visible' ) ) {
				return;
			}

			this.options.menu.deactivate();
			this.options.menu.element.hide();

			this._trigger( 'close' );
		},

		/**
		 * Aligns the menu to the input element.
		 */
		repositionMenu: function () {
			var dir = this.element.attr( 'dir' )
			|| $( document.documentElement ).css( 'direction' )
			|| 'auto';

			var position = $.extend( {}, this.options.position ),
				$menu = this.options.menu.element;

			if ( dir === 'rtl' ) {
				position = flipPosition( position );
			}

			$menu.position( $.extend( {
				of: this.element
			}, position ) );

			$menu.zIndex( this.element.zIndex() + 1 );

			if ( this.element.attr( 'lang' ) ) {
				$menu.attr( 'lang', this.element.attr( 'lang' ) );
			}
			$menu.attr( 'dir', dir );

			this.options.menu.scale();

			if ( this.options.confineMinWidthTo !== null ) {
				var $minWidthConfinement = this.options.confineMinWidthTo || this.element;

				$menu.css(
					'min-width',
					$minWidthConfinement.outerWidth() - ( $menu.outerWidth() - $menu.width() )
				);
			}
		}

	} );

}() );

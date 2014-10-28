/**
 * jQuery.ui.suggester enhances an input box by retrieving a list of suggestions that are displayed
 * in a list below the input box.
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @example $( 'input' ).suggester( { source: ['a', 'b', 'c'] } );
 * @desc Creates a simple suggester using an array as result set.
 *
 * @example $( 'input' ).suggester( {
 *   source: function( term ) {
 *     var deferred = $.Deferred();
 *
 *     $.ajax( {
 *       url: location.protocol + '//commons.wikimedia.org/w/api.php',
 *       dataType: 'jsonp',
 *       data: {
 *         search: term,
 *         action: 'opensearch',
 *         namespace: 6
 *       },
 *       timeout: 8000
 *     } )
 *     .done( function( response ) {
 *       deferred.resolve( response[1], response[0] );
 *     } )
 *     .fail( function( jqXHR, textStatus ) {
 *       deferred.reject( textStatus );
 *     } );
 *
 *     return deferred.promise();
 *   }
 * } );
 * @desc Creates an auto-completion input element fetching suggestions via AJAX.
 *
 * @option {string[]|Function} source
 *         An array of strings that shall be used to provide suggestions. Alternatively, a function
 *         may be provided:
 *         Parameters:
 *         - {string} Search term
 *         Expected return values:
 *         - {Object} jQuery promise
 *           Resolved parameters:
 *           - {string[]} Suggestions
 *           - {string} (optional) Search term corresponding to the suggestions. This allows
 *             checking whether the response belongs to the most current request.
 *           Rejected parameters:
 *           - {string} Plain text or HTML error message.
 *
 * @option {number} [delay]
 *         Delay in milliseconds of the request querying for suggestions.
 *         Default: 300
 *
 * @option {jQuery.ui.ooMenu|null} [menu]
 *         A pre-initialized menu instance featuring one or more custom list item may be provided.
 *         This should be the preferred way to define custom items.
 *         Default: null (no default menu)
 *
 * @option {Object} [position]
 *         Object to be evaluated by jQuery.ui.position to set the suggestion list's position. In
 *         RTL context, the specified value is flipped automatically.
 *         Default: (position suggestion list's top left corner at input box's bottom left corner)
 *
 * @option {jQuery|null} [confineMinWidthTo]
 *         The suggestion list's width shall not be smaller than the width of the referenced
 *         element. If "undefined", the minimum width will be the width of the element the suggester
 *         is initialized on. Specifying "null" will prevent applying a minimum width.
 *         Default: undefined
 *
 * @event open
 *        Triggered when the list of suggestions is opened.
 *        - {jQuery.Event}
 *
 * @event close
 *        Triggered when the list of suggestions is closed.
 *        - {jQuery.Event}
 *
 * @event change
 *        Triggered when the suggester's value has changed.
 *        - {jQuery.Event}
 *
 * @event error
 *        Triggered whenever an error occurred while gathering suggestions. This may happen only
 *        when using a function as source. The {string} parameter is forwarded from the rejected
 *        promise returned by the source function.
 *        - {jQuery.Event}
 *        - {string}
 *
 * @dependency jQuery.ui.ooMenu
 * @dependency jQuery.ui.position
 */
( function( $ ) {
	'use strict';

	$.widget( 'ui.suggester', {

		/**
		 * @see jQuery.Widget.options
		 */
		options: {
			source: null,
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
		 * @type {number}
		 */
		_pending: null,

		/**
		 * Current search term.
		 * @type {string}
		 */
		_term: null,

		/**
		 * Minimum amount of characters to begin a search.
		 * @type {int}
		 */
		_minTermLength: 1,

		/**
		 * @see jQuery.Widget._create
		 */
		_create: function() {
			var self = this;

			this._pending = 0;
			this._term = this.element.val();

			this.element
			.addClass( 'ui-suggester-input' )
			.on( 'blur.' + this.widgetName, function() {
				if( !self.options.menu.element.is( ':focus' ) ) {
					self._close();
				}
			} );

			if( !( this.options.menu instanceof $.ui.ooMenu ) ) {
				var $menu = $( '<ul/>' ).ooMenu();
				this.options.menu = $menu.data( 'ooMenu' );
			}

			this.options.menu = this._initMenu( this.options.menu );

			this._attachInputEventHandlers();
			this._attachWindowEventHandlers();
		},

		/**
		 * @see jQuery.Widget.destroy
		 */
		destroy: function() {
			var menu = this.option( 'menu' );
			menu.destroy();
			menu.element.remove();
			this.option( 'menu', null );

			// About to remove the last suggester instance on the page:
			if ( $( ':' + this.widgetBaseClass ).length === 1 ) {
				$( window ).off( '.' + this.widgetBaseClass );
			}

			this.element.removeClass( 'ui-suggester-input' );
			this.element.removeClass( 'ui-suggester-loading' );
			this.element.removeClass( 'ui-suggester-error' );

			$.Widget.prototype.destroy.call( this );
		},

		/**
		 * @see jQuery.Widget._setOption
		 */
		_setOption: function( key, value ) {
			if( key === 'menu' ) {
				this.options.menu.destroy();
				this.options.menu.element.remove();
			}

			var response = $.Widget.prototype._setOption.apply( this, arguments );

			if( key === 'menu' && value instanceof $.ui.ooMenu ) {
				this.options.menu = this._initMenu( value );
			}

			if( key === 'disabled' ) {
				if( value ) {
					this._close();
				}
				this.element.prop( 'disabled', value );
			}

			return response;
		},

		/**
		 * Renders the menu and attaches the menu's event handlers.
		 *
		 * @param {jQuery.ui.ooMenu} ooMenu
		 * @return {jQuery.ui.ooMenu}
		 */
		_initMenu: function( ooMenu ) {
			var self = this;

			ooMenu.element
			.addClass( 'ui-suggester-list' )
			.hide()
			.appendTo( 'body' );

			$( ooMenu )
			.on( 'selected.suggester', function( event, item ) {
				if( item instanceof $.ui.ooMenu.Item && !( item instanceof $.ui.ooMenu.CustomItem ) ) {
					self._term = item.getValue();
					self.element.val( item.getValue() );
					self._close();
					self._trigger( 'change' );

					setTimeout( function() {
						self.element.focus();
					}, 0 );
				}
			} );

			return ooMenu;
		},

		/**
		 * Attaches input event handlers to the input element.
		 */
		_attachInputEventHandlers: function() {
			var self = this,
				suppressKeyPress = false;

			this.element
			.on( 'keydown.suggester', function( event ) {
				var isDisabled = self.element.hasClass( 'ui-state-disabled' );

				if( isDisabled || self.element.prop( 'readOnly' ) ) {
					return;
				}

				self.element.removeClass( 'ui-suggester-error' );

				suppressKeyPress = false;

				var keyCode = $.ui.keyCode;

				switch( event.keyCode ) {
					case keyCode.UP:
						self._keyMove( 'previous', event );
						break;

					case keyCode.DOWN:
						self._keyMove( 'next', event );
						break;

					case keyCode.ENTER:
					case keyCode.NUMPAD_ENTER:
						if( self.options.menu.getActiveItem() ) {
							// Prevent form submission and select currently active item.
							event.preventDefault();
							event.stopPropagation();
							suppressKeyPress = true;
							self.options.menu.select( event );
						}
						break;

					case keyCode.TAB:
						if( !self.options.menu.getActiveItem() ) {
							return;
						}
						self.options.menu.select( event );
						break;

					case keyCode.ESCAPE:
						self.element.val( self._term );
						if( self.options.menu.element.is( ':visible' ) ) {
							event.stopPropagation();
							self._close();
						}
						break;

					default:
						if( self.element.val() === ''
							&& (
								event.keyCode === keyCode.BACKSPACE
								|| event.keyCode === keyCode.DELETE
							)
						) {
							break;
						}

						clearTimeout( self.__searching );
						self.__searching = setTimeout( function() {
							// Only search if the value has changed:
							if( self._term !== self.element.val() ) {
								self._selectedItem = null;
								self.search( event )
								.done( function() {
									// Widget might have been destroyed in the meantime.
									if( self.element.data( this.widgetName ) ) {
										self._trigger( 'change' );
									}
								} );
							}
						}, self.options.delay );
						break;
				}

				self._trigger( 'change' );
			} )
			.on( 'keypress.suggester', function( event ) {
				if( suppressKeyPress ) {
					suppressKeyPress = false;
					event.preventDefault();
				}
			} );
		},

		/**
		 * Attaches event listeners to the "window" object.
		 */
		_attachWindowEventHandlers: function() {
			var self = this;

			$( window )
			.off( '.' + this.widgetBaseClass )
			.on( 'resize.' + this.widgetBaseClass, function() {
				$( ':' + self.widgetBaseClass ).each( function( i, node ) {
					var suggester = $( node ).data( self.widgetName );
					suggester.repositionMenu();
					suggester.options.menu.scale();
				} );
			} )
			.on( 'click.' + this.widgetBaseClass, function( event ) {
				var $target = $( event.target );
				$( ':' + self.widgetBaseClass ).each( function( i, node ) {
					var suggester = $( node ).data( self.widgetName );
					// Close suggester if not clicked on suggester or corresponding list:
					if(
						$target.closest( suggester.element ).length === 0
						&& $target.closest( suggester.options.menu.element ).length === 0
					) {
						suggester._close();
					}
				} );
			} );
		},

		/**
		 * Handles moving through the list of suggestions using arrow keys.
		 *
		 * @param {string} direction (either "previous" or "next")
		 * @param {jQuery.Event} event
		 */
		_keyMove: function( direction, event ) {
			// Prevent moving cursor to beginning/end of the text field in some browsers:
			event.preventDefault();

			if( !this.options.menu.element.is( ':visible' ) ) {
				clearTimeout( this.__searching );
				this._cache = {};
				this.search( event );
				return;
			}

			var allItems = $.merge( [], this.options.menu.option( 'items' ) );
			$.merge( allItems, this.options.menu.option( 'customItems' ) );

			if( allItems.length > 0 ) {
				this._move( direction, this.options.menu.getActiveItem(), allItems );
			}
		},

		/**
		 * Shifts the suggestions menu focus by one item.
		 *
		 * @param {string} direction
		 * @param {jQuery.ui.ooMenu.Item} activeItem
		 * @param {jQuery.ui.ooMenu.Item[]} allItems
		 */
		_move: function( direction, activeItem, allItems ) {
			var self = this,
				isFirst = activeItem === allItems[0],
				isLast = activeItem === allItems[allItems.length - 1];

			if( isFirst && direction === 'previous' || isLast && direction === 'next' ) {
				this._moveOffEdge( direction );
			} else {
				$( this.options.menu ).one( 'focus.suggester', function( event, item ) {
					var isCustomMenuItem = item instanceof $.ui.ooMenu.CustomItem;

					if( item instanceof $.ui.ooMenu.Item && !isCustomMenuItem ) {
						self.element.val( item.getValue() );
					} else if( isCustomMenuItem ) {
						self.element.val( self._term );
					}
					self._trigger( 'change' );
				} );
				this.options.menu[direction]();
			}
		},

		/**
		 * Handler called when the suggestion menu focus is to be shifted off the end of the list.
		 *
		 * @param {string} direction
		 */
		_moveOffEdge: function( direction ) {
			this.element.val( this._term );
			this.options.menu.deactivate();
		},

		/**
		 * Performs a search on the current input.
		 *
		 * @param {jQuery.Event} event The original event that triggered the search.
		 * @return {jQuery.Promise}
		 *         Resolved parameters:
		 *         - {string[]}
		 *         Rejected parameters:
		 *         - {string}
		 */
		search: function( event ) {
			var self = this,
				deferred = $.Deferred();

			this._term = this.element.val();

			if( this._term.length < this._minTermLength ) {
				this._close();
				return deferred.resolve( [], this._term ).promise();
			}

			this.element.addClass( 'ui-suggester-loading' );
			this._pending++;

			return this._getSuggestions( this._term )
			.done( function( suggestions, requestTerm ) {
				if( typeof requestTerm === 'string' && requestTerm !== self._term ) {
					// Skip request since it does not correspond to the current search term.
					return;
				}
				self._updateMenu( suggestions, requestTerm );
			} )
			.fail( function( message ) {
				self.element.addClass( 'ui-suggester-error' );
				self._trigger( 'error', null, [message] );
			} )
			.always( function() {
				if( --self._pending === 0 ) {
					self.element.removeClass( 'ui-suggester-loading' );
				}
			} );
		},

		/**
		 * Updates the menu.
		 *
		 * @param {string[]} suggestions
		 * @param {string} requestTerm
		 */
		_updateMenu: function( suggestions, requestTerm ) {
			this._updateMenuItems( suggestions, requestTerm );
			this._updateMenuVisibility();
		},

		/**
		 * Updates the suggestion menu with the received suggestions.
		 *
		 * @param {string[]} suggestions
		 * @param {string} requestTerm
		 */
		_updateMenuItems: function( suggestions, requestTerm ) {
			var menuItems = [];

			for( var i = 0; i < suggestions.length; i++ ) {
				menuItems.push( this._createMenuItemFromSuggestion( suggestions[i], requestTerm ) );
			}

			this.options.menu.option( 'items', menuItems );
		},

		/**
		 * Updates the menu's visibility.
		 */
		_updateMenuVisibility: function() {
			if( !this.options.menu.hasVisibleItems( true ) ) {
				this._close();
			} else {
				this._open();
				this.repositionMenu();
			}
		},

		/**
		 * Instantiates a menu item instance from a suggestion.
		 *
		 * @param {string} suggestion
		 * @param {string} requestTerm
		 * @return {jQuery.ui.ooMenu.Item}
		 */
		_createMenuItemFromSuggestion: function( suggestion, requestTerm ) {
			return new $.ui.ooMenu.Item( suggestion );
		},

		/**
		 * Retrieves the suggestions for a specific search term.
		 *
		 * @param {string} term
		 * @return {jQuery.Promise}
		 *         Resolved parameters:
		 *         - {string[]} suggestions
		 *         - {string} requestTerm
		 *         Rejected parameters:
		 *         - {string}
		 */
		_getSuggestions: function( term ) {
			if ( typeof this.options.source === 'function' ) {
				return this.options.source( term );
			}

			return this._getSuggestionsFromArray( term, this.options.source );
		},

		/**
		 * Filters an array using a specific search term.
		 *
		 * @param {string} term
		 * @param {string[]} source
		 * @return {jQuery.Promise}
		 *         Resolved parameters:
		 *         - {string[]} suggestions
		 *         - {string} requestTerm
		 *         Promise may not be rejected.
		 */
		_getSuggestionsFromArray: function( term, source ) {
			var deferred = $.Deferred();

			var matcher = new RegExp( this._escapeRegex( term ), 'i' );

			deferred.resolve( $.grep( source, function( item ) {
				return matcher.test( item );
			} ), term );

			return deferred.promise();
		},

		/**
		 * Escapes a string to be used in a regular expression.
		 *
		 * @param {string} value
		 * @return {string}
		 */
		_escapeRegex: function( value ) {
			return value.replace( /[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&' );
		},

		/**
		 * Shows the suggester menu.
		 */
		_open: function() {
			if( this.options.menu.element.is( ':visible' ) ) {
				return;
			}

			this.options.menu.element.show();
			this.repositionMenu();

			this._trigger( 'open' );
		},

		/**
		 * Hides the suggester menu.
		 */
		_close: function() {
			if( !this.options.menu.element.is( ':visible' ) ) {
				return;
			}

			this.options.menu.deactivate();
			this.options.menu.element.hide();

			this._trigger( 'close' );
		},

		/**
		 * Aligns the menu to the input element.
		 */
		repositionMenu: function() {
			var dir = this.element.attr( 'dir' )
				|| $( document.documentElement ).css( 'direction' )
				|| 'auto';

			var position = $.extend( {}, this.options.position ),
				$menu = this.options.menu.element;

			if( dir === 'rtl' ) {
				position = flipPosition( position );
			}

			$menu.position( $.extend( {
				of: this.element
			}, position ) );

			$menu.zIndex( this.element.zIndex() + 1 );

			if( this.element.attr( 'lang' ) ) {
				$menu.attr( 'lang', this.element.attr( 'lang' ) );
			}
			$menu.attr( 'dir', dir );

			this.options.menu.scale();

			if( this.options.confineMinWidthTo !== null ) {
				var $minWidthConfinement = this.options.confineMinWidthTo || this.element;

				$menu.css(
					'min-width',
					$minWidthConfinement.outerWidth() - ( $menu.outerWidth() - $menu.width() )
				);
			}
		}

	} );

	/**
	 * Flips a complete position specification to be used by jQuery.ui.position (1.8).
	 *
	 * @param {Object} position
	 * @return {Object}
	 */
	function flipPosition( position ) {
		function flipOrientation( orientation ) {
			if( /right/i.test( orientation ) ) {
				return orientation.replace( /right/i, 'left' );
			} else {
				return orientation.replace( /left/i, 'right' );
			}
		}

		function flipHorizontalOffset( offset ) {
			var offsets = offset.split( ' ' ),
				hOffset = parseInt( offsets[0], 10 );

			hOffset = ( hOffset <= 0 ) ? Math.abs( hOffset ) : hOffset * -1;
			return hOffset + ' ' + offsets[1];
		}

		position.my = flipOrientation( position.my );
		position.at = flipOrientation( position.at );

		if( position.offset ) {
			position.offset = flipHorizontalOffset( position.offset );
		}

		return position;
	}

} )( jQuery );

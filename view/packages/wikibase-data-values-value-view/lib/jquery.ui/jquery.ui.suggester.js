/**
 * Suggester widget enhancing jquery.ui.autocomplete
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * jquery.ui.suggester adds a few enhancements to jquery.ui.autocomplete, e.g. adding a scrollbar
 * when a certain number of items is listed in the suggestion list, highlighting matching characters
 * in the suggestions and dealing with language direction.
 * Specifying 'ajax.url' and 'ajax.params' parameters will trigger using a custom function to
 * handle the server response (_request()). Alternatively, an array may be passed as
 * source or a completely custom function - both is covered by native jquery.ui.autocomplete
 * functionality.
 * See jquery.ui.autocomplete for further documentation - just listing additional options here.
 *
 * @example $( 'input' ).suggester( { source: ['a', 'b', 'c'] } );
 * @desc Creates a simple auto-completion input element passing an array as result set.
 *
 * @example $( 'input' ).suggester( {
 *   ajax: {
 *     url: <url>,
 *     params: { <additional parameters> }
 *   }
 * } );
 * @desc Creates an auto-completion input element fetching suggestions via AJAX.
 *
 * @option maxItems {Number|null} (optional) If the number of suggestions is higher than maxItems,
 *         the suggestion list will be made scrollable. Setting maxItems to null will automatically
 *         resize the suggestion list's height.
 *         Default value: 10
 *
 * @option ajax.url {String} (optional) URL to fetch suggestions from (if these shall be queried
 *         via AJAX)
 *         Default value: null
 *
 * @option ajax.params {Object} (optional) Additional AJAX parameters (if suggestions shall be
 *         retrieved via AJAX)
 *         Default value: {}
 *
 * @option ajax.timeout {Number} (optional) AJAX timeout in milliseconds.
 *         Default value: 8000
 *
 * @option adaptLetterCase {String|Boolean} (optional) Defines whether to adjust the letter case
 *         according to the suggestion list's first value whenever the suggestion list is filled.
 *         Possible values: false, 'first', 'all'
 *         Default value: false
 *
 * @option replace {Array} (optional) Array containing a regular expression and a replacement
 *         pattern (e.g. [/^File:/, '']) that is applied to each result returned by the API.
 *         Default value: null (no replacing)
 *
 * @option customListItem {Object|Boolean} (optional) A custom item appended to the suggestion list.
 *         Default value: false (no custom list item)
 *         Example:
 *           {
 *             content: 'custom item label',
 *             action: function( event, suggester ) {
 *               console.log( suggester.element.val() );
 *               suggester.close();
 *             }
 *           }
 * @option customListItem.content {jQuery|String} The content of the additional list item. The
 *         content will be wrapped in a link node inside a list node (<li><a>content</a></li>).
 *         For custom styling, the css class 'ui-suggester-custom' is assigned to the <li/> node.
 * @option customListItem.action {Function} The action to perform when selecting the additional
 *         list item.
 *         Parameters: (1) {jQuery.Event} Event that has triggered the custom action
 *                     (2) {$.ui.suggester} Reference to the suggester widget
 * @option customListItem.cssClass {String} (optional) Additional css class(es) to assign to the custom
 *         item's <li/> node.
 *
 * @event response Triggered when the API call returned successful.
 *        (1) {jQuery.Event}
 *        (2) {Array} List of retrieved items.
 *
 * @event error Triggered when the API call was not successful.
 *        (1) {jQuery.Event}
 *        (2) {String} Error text status.
 *        (3) {Object} Detailed error information.
 *
 * @dependency jquery.autocompletestring
 * @dependency jquery.ui.autocomplete
 * @dependency jquery.util.adaptlettercase
 * @dependency jquery.util.getscrollbarwidth
 */
( function( $ ) {
	'use strict';

	$.widget( 'ui.suggester', $.ui.autocomplete, {

		/**
		 * Additional options
		 * @type {Object}
		 */
		options: {
			maxItems: 10,
			ajax: {
				url: null,
				params: {},
				timeout: 8000
			},
			adaptLetterCase: false,
			replace: null,
			customListItem: false
		},

		/**
		 * Caching the last pressed key's code
		 * @type {Number}
		 */
		_lastKeyDown: null,

		/**
		 * @see ui.autocomplete._create
		 */
		_create: function() {
			var self = this;

			if ( this.options.source === null ) {
				this.options.source = this._request;
			}

			$.ui.autocomplete.prototype._create.call( this );

			if ( $.isArray( this.options.source ) ) {
				this.source = this._filterArray;
			}

			// Get rid of autocomplete's native blur handling resetting the input (causing the
			// auto-completed string to be dropped).
			this.menu.option( 'blur', null );

			/**
			 * @see ui.menu.refresh
			 */
			this.menu.refresh = function() {
				self._trigger( 'refreshmenu' );
				$.ui.menu.prototype.refresh.call( this );
			};

			this.element
			.addClass( 'ui-suggester-input' )
			.on( this.widgetName + 'open.' + this.widgetName, function( event ) {
				self._updateDirection();
				self._highlightMatchingCharacters();
			} )
			.on( this.widgetName + 'refreshmenu.' + this.widgetName, function( event ) {
				if ( self.options.customListItem ) {
					self._renderCustomListItem( self.options.customListItem );
				}
			} )
			.on( 'keydown.' + this.widgetName, function( event ) {
				if ( event.keyCode === $.ui.keyCode.ENTER ) {
					if ( self.menu.active ) {
						var item = self.menu.active.data( 'item.autocomplete' );
						if ( item && item.isCustom && item.customAction ) {
							// Custom actions are supposed to be suggester-specific. If, for some
							// reason, they should interact with external components, the action(s)
							// may trigger custom events.
							item.customAction( event, self );
							return;
						}
					}
				}
				self._lastKeyDown = event.keyCode;
			} );

			this.menu.element.addClass( 'ui-suggester-list' );

			// Extend menu's selected method to be able to trigger custom item's action.
			var fnNativeMenuSelected = this.menu.option( 'selected' );
			this.menu.option( 'selected', function( event, ui ) {
				var item = ui.item.data( 'item.autocomplete' );
				if ( !item.isCustom ) {
					fnNativeMenuSelected( event, ui );
				} else if ( $.isFunction( item.customAction ) ) {
					item.customAction( event, self );
				}
			} );

			// since results list does not reposition automatically on resize, just close it
			// (one resize event handler is enough for all widgets)
			$( window )
			.off( '.' + this.widgetName )
			.on( 'resize.' + this.widgetName, function( event ) {
				$( ':' + self.widgetBaseClass ).each( function( i, node ) {
					$( node ).data( self.widgetName ).close( {} );
				} );
			} );
		},

		/**
		 * @see ui.autocomplete.destroy
		 */
		destroy: function() {
			// about to remove the last suggester instance on the page
			if ( $( ':' + this.widgetBaseClass ).length === 1 ) {
				$( window ).off( '.' + this.widgetName );
			}
			this.element.off( '.' + this.widgetName );
			this.element.removeClass( 'ui-suggester-input' );
			$.ui.autocomplete.prototype.destroy.call( this );
		},

		/**
		 * Disables the suggester.
		 */
		disable: function() {
			this.close();
			this.element.prop( 'disabled', true ).addClass( 'ui-state-disabled' );
		},

		/**
		 * Enables the suggester.
		 */
		enable: function() {
			this.element.prop( 'disabled', false ).addClass( 'ui-state-disabled' );
		},

		/**
		 * Filters an array passed as suggestion source.
		 *
		 * @param {Object} request
		 * @param {Function} response
		 */
		_filterArray: function( request, response ) {
			var resultSet = $.ui.autocomplete.filter( this.options.source, request.term ),
				firstLabel = resultSet[0];

			if( $.isPlainObject( resultSet[0] ) ) {
				firstLabel = resultSet[0].label;
			}

			if ( resultSet.length && this.options.adaptLetterCase ) {
				this.term = $.util.adaptLetterCase( this.term,
					firstLabel,
					this.options.adaptLetterCase
				);
				this.element.val( this.term );
			}

			response( resultSet );

			if( this._lastKeyDown !== $.ui.keyCode.BACKSPACE ) {
				this.element.autocompletestring( request.term, firstLabel );
			}
		},

		/**
		 * Performs the AJAX request.
		 *
		 * @param request {Object} Contains request parameters
		 * @param suggest {Function} Callback putting results into auto-complete menu
		 */
		_request: function( request, suggest ) {
			$.ajax( {
				url: this.options.ajax.url,
				dataType: 'jsonp',
				data:  $.extend( {}, this.options.ajax.params, { 'search': request.term } ),
				timeout: this.options.ajax.timeout,
				success: $.proxy( this._success, this ),
				error: $.proxy( function( jqXHR, textStatus, errorThrown ) {
					suggest();
					this.element.focus();
					this._trigger( 'error', $.Event(), [textStatus, errorThrown] );
				}, this )
			} );
		},

		/**
		 * @see jquery.ui.autocomplete.__response
		 */
		__response: function( content ) {
			$.ui.autocomplete.prototype.__response.call( this, content );
			// There is no content but the menu should be visible if there is a custom list item:
			if ( !this.options.disabled && ( !content || !content.length ) && this.customListItem ) {
				this._suggest( [] );
				this._trigger( 'open' );
			}
		},

		/**
		 * Handles the response when the API call returns successfully.
		 *
		 * @param {Object} response
		 */
		_success: function( response ) {
			var suggest = this._response();
			if ( response[0] === this.element.val() ) {

				var self = this;
				if ( this.options.replace !== null ) {
					$.each( response[1], function( i, value ) {
						response[1][i] = value.replace( self.options.replace[0], self.options.replace[1] );
					} );
				}

				// auto-complete input box text (because of the API call lag, this is
				// avoided when hitting backspace, since the value would be reset too slow)
				if ( this._lastKeyDown !== $.ui.keyCode.BACKSPACE && response[1].length > 0 ) {
					var incomplete = response[0],
						complete = response[1][0];

					if ( this.options.adaptLetterCase ) {
						this.term = incomplete = $.util.adaptlettercase(
							incomplete,
							complete,
							this.options.adaptLetterCase
						);
					}

					this.element.autocompletestring( incomplete, complete );
				}

				suggest( response[1] ); // pass array of returned values to callback

				this._trigger( 'response', $.Event(), [response[1]] );
			} else {
				// suggest nothing when the response does not match with the current input value
				// informing autocomplete that there is one less pending request
				suggest();
			}
		},

		/**
		 * @see ui.autocomplete._suggest
		 */
		_suggest: function( items ) {
			$.ui.autocomplete.prototype._suggest.call( this, items );
			// In $.ui.autocomplete, _resizeMenu() is called before positioning the menu. However,
			// resizing the menu width has to be performed after positioning since the width shall
			// be constrained by the browser viewport width.
			this._scaleMenu();
		},

		/**
		 * Scales the menu's height to the height of maximum list items and takes care of the menu
		 * width not reaching out of the browser viewport.
		 */
		_scaleMenu: function() {
			this._resetMenuStyle();
			var $menu = this.menu.element;

			if ( this.options.maxItems ) {
				if ( $menu.children().length > this.options.maxItems ) {
					var fixedHeight = 0;
					for ( var i = 0; i < this.options.maxItems; i++ ) {
						fixedHeight += $( $menu.children()[i] ).height();
					}
					$menu.width( $menu.width() + $.util.getscrollbarwidth() );
					$menu.height( fixedHeight );
					$menu.css( 'overflowY', 'scroll' );
				}
			}

			$menu.css(
				'minWidth',
				this.element.outerWidth( true ) - ( $menu.outerWidth( true ) - $menu.width() ) + 'px'
			);

			$menu.width( $menu.outerWidth( true ) );

			// menu reaches out of the browser viewport
			if ( $menu.offset().left + $menu.outerWidth( true ) > $( window ).width() ) {
				// force maximum menu width
				$menu.width(
					$( window ).width()
						- $menu.offset().left
						- ( $menu.outerWidth( true ) - $menu.width() )
						- 20 // safe space
				);
			}
		},

		/**
		 * Renders a custom list item and appends it to the suggestion list.
		 * @see ui.autocomplete._renderItem
		 *
		 * @param {Object} customListItem Custom list item definition (see option description)
		 * @return {jQuery} The new list item
		 */
		_renderCustomListItem: function( customListItem ) {
			var content = customListItem.content,
				$li = $( '<li/>' )
					.addClass( 'ui-suggester-custom' )
					.data( 'item.autocomplete', {
						isCustom: true,
						customAction: customListItem.action,
						// internal autocomplete logic needs a value (e.g. for activating)
						value: this.term
					} ),
				$a = $( '<a/>' ).appendTo( $li );

			if ( customListItem.cssClass ) {
				$li.addClass( customListItem.cssClass );
			}

			if ( typeof content === 'string' ) {
				$a.text( content );
			} else if ( content instanceof $ ) {
				$a.append( content );
			} else {
				throw new Error( 'suggester: Custom list item is invalid.' );
			}

			if ( this.menu.element.children( '.ui-suggester-custom' ).length > 0 ) {
				// TODO: This is entity selector "more" button specific. There should be a method
				// to specify a position where to add the custom list item.
				return this.menu.element.children( '.ui-suggester-custom' ).first().before( $li );
			} else {
				return $li.appendTo( this.menu.element );
			}
		},

		/**
		 * Sets (updates) or gets the custom list item.
		 *
		 * @param {Object} [customListItem] Custom list item (omit to get the current custom list
		 *        item in the form of a jQuery node). For the object structure of this parameter see
		 *        the customListItem option description.
		 * @return {jQuery|String|Boolean} The custom list item's content or false if none is
		 *         defined
		 */
		customListItem: function( customListItem ) {
			if ( customListItem === undefined ) {
				if ( !this.options.customListItem ) {
					return false;
				}
				var $a = this.menu.element.children( '.ui-suggester-custom a' );
				if ( typeof this.options.customListItem === 'string' ) {
					return $a.text();
				} else {
					return $a.children();
				}
			} else {
				this.options.customListItem = customListItem;
				this.menu.refresh();
				return this.customListItem();
			}
		},

		/**
		 * Resets the menu css styles.
		 */
		_resetMenuStyle: function() {
			this.menu.element
			.css( 'minWidth', 'auto' )
			.width( 'auto' )
			.height( 'auto' )
			.css( 'overflowY', 'ellipsis' );
		},

		/**
		 * Calculates the menu height (including all menu items - even those out of the viewport).
		 *
		 * @return {Number} menu height
		 */
		_getMenuHeight: function() {
			this._resetMenuStyle();
			var height = 0;
			this.menu.element.children( 'li' ).each( function( i ) {
				height += $( this ).height();
			} );
			return height;
		},

		/**
		 * Makes autocomplete results list stretch from the right side of the input box in rtl.
		 */
		_updateDirection: function() {
			if (
				this.element.attr( 'dir' ) === 'rtl' ||
					(
						this.element.attr( 'dir' ) === undefined
						&& document.documentElement.dir === 'rtl'
					)
				) {
				this.options.position.my = 'right top';
				this.options.position.at = 'right bottom';

				if( this.options.position.offset ) {
					this.options.position.offset = flipHorizontalOffset(
						this.options.position.offset
					);
				}

				this.menu.element.position( $.extend( {
					of: this.element
				}, this.options.position ) );

				// to display rtl and ltr correctly
				// sometimes a rtl wiki can have ltr page names, etc. (try ".gov")
				this.menu.element.children().attr( {
					'dir': 'auto'
				} );
			}
		},

		/**
		 * Highlights matching characters in the result list.
		 */
		_highlightMatchingCharacters: function() {
			var term = ( this.term ) ? this.term : '',
				escapedTerm = $.ui.autocomplete.escapeRegex( term ),
				regExp = new RegExp(
					'((?:(?!' + escapedTerm +').)*?)(' + escapedTerm + ')(.*)', ''
				);

			this.menu.element.children( '.ui-menu-item' ).each( function() {
				if ( !$( this ).data( 'item.autocomplete' ).isCustom ) {
					var $itemLink = $( this ).find( 'a' );

					// only replace if suggestions actually starts with the current input
					if ( $itemLink.text().indexOf( term ) === 0 ) {
						var matches = $itemLink.text().match( regExp );

						$itemLink
						.text( matches[1] )
						.append( $( '<b/>' ).text( matches[2] ) )
						.append( document.createTextNode( matches[3] ) );
					}
				}
			} );
		},

		/**
		 * Sets/gets the plain input box value.
		 *
		 * @param {String} [value] Value to be set
		 * @return {String} value Current/new value
		 */
		value: function( value ) {
			if ( value !== undefined ) {
				this.element.val( value );
			}
			return this.element.val();
		},

		/**
		 * Resets/updates the menu position.
		 */
		repositionMenu: function() {
			this.menu.element.position( $.extend( {
				of: this.element
			}, this.options.position ) );
		}

	} );

	/**
	 * Flips the horizontal offset of a offset specified as string to be used as offset parameter
	 * for jQuery.ui.position (1.8)
	 *
	 * @param {string} offset
	 * @return {string}
	 */
	function flipHorizontalOffset( offset ) {
		var offsets = offset.split( ' ' ),
			hOffset = parseInt( offsets[0], 10 );

		hOffset = ( hOffset <= 0 ) ? Math.abs( hOffset ) : hOffset * -1;
		return hOffset + ' ' + offsets[1];
	}

} )( jQuery );

/**
 * Suggester widget enhancing jquery.ui.autocomplete
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
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
 * @option maxItems {Number} (optional) If the number of suggestions is higher than maxItems,
 *         the suggestion list will be made scrollable.
 *         Default value: 10
 *
 * @option ajax.url {String} (optional) URL to fetch suggestions from (if these shall be queried
 *         via AJAX)
 *         Default value: null
 *
 * @option ajax.params {Object} (optional) Additional AJAX parameters (if suggestions shall be
 *         retrievend via AJAX)
 *         Default value: {}
 *
 * @option ajax.timeout {Number} (optional) AJAX timeout in milliseconds.
 *         Default value: 8000
 *
 * @option replace {Array} (optional) Array containing a regular expression and a replacement
 *         pattern (e.g. [/^File:/, '']) that is applied to each result returned by the API.
 *         Default value: null (no replacing)
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
 * @dependency jquery.ui.autocomplete
 */
( function( $, undefined ) {
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
			replace: null
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

			this.element
			.addClass( 'ui-suggester-input' )
			.on( this.widgetName + 'open.' + this.widgetName, function( event ) {
				self._updateDirection();
				self._highlightMatchingCharacters();
			} )
			.on( 'keydown.' + this.widgetName, function( event ) {
				self._lastKeyDown = event.keyCode;
			} );

			this.menu.element.addClass( 'ui-suggester-list' );

			// since results list does not reposition automatically on resize, just close it
			// (one resize event handler is enough for all widgets)
			$( window )
			.off( '.' + this.widgetName )
			.on( 'resize.' + this.widgetName, function( event ) {
				if ( event.originalEvent === undefined && $( '.ui-suggester-input' ).length > 0 ) {
					$( '.ui-suggester-input' ).data( self.widgetName ).close( {} );
				}
			} );
		},

		/**
		 * @see ui.autocomplete.destroy
		 */
		destroy: function() {
			// about to remove the last suggester instance on the page
			if ( $( '.ui-suggester-input' ).length === 1 ) {
				$( window ).off( '.' + this.widgetName );
			}
			this.element.off( '.' + this.widgetName );
			this.element.removeClass( 'ui-suggester-input' );
			$.ui.autocomplete.prototype.destroy.call( this );
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

				suggest( response[1] ); // pass array of returned values to callback

				// auto-complete input box text (because of the API call lag, this is
				// avoided when hitting backspace, since the value would be reset too slow)
				if ( this._lastKeyDown !== 8 && response[1].length > 0 ) {
					this.autocompleteString(
						response[0],
						response[1][0]
					);
				}
				this._trigger( 'response', $.Event(), [response[1]] );
			} else {
				// suggest nothing when the response does not match with the current input value
				// informing autocomplete that there is one less pending request
				suggest();
			}
		},

		/**
		 * Resizes the menu's height to the height of maximum list items.
		 *
		 * @see ui.autocomplete._resizeMenu
		 */
		_resizeMenu: function() {
			$.ui.autocomplete.prototype._resizeMenu.call( this );

			this._resetMenuStyle();
			var $menu = this.menu.element;
			if ( $menu.children().length > this.options.maxItems ) {
				var fixedHeight = 0;
				for ( var i = 0; i < this.options.maxItems; i++ ) {
					fixedHeight += $( $menu.children()[i] ).height();
				}
				$menu.width( $menu.width() + this._getScrollbarWidth() );
				$menu.height( fixedHeight );
				$menu.css( 'overflowY', 'scroll' );
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
		 * Calculates the width of the browser's scrollbar.
		 *
		 * @returns {Number} scrollbar width
		 */
		_getScrollbarWidth: function() {
			var $inner = $( '<p/>', { style: 'width:100px;height:100px' } ),
				$outer = $( '<div/>', {
					style: 'position:absolute;top:-1000px;left:-1000px;visibility:hidden;'
						+ 'width:50px;height:50px;overflow:hidden;'
				} ).append( $inner ).appendTo( $( 'body' ) ),
				majorWidth = $outer[0].clientWidth;

			$outer.css( 'overflow', 'scroll' );
			var minorWidth = $outer[0].clientWidth;
			$outer.remove();
			return ( majorWidth - minorWidth );
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
			var value = this.element.val(),
				escapedValue = $.ui.autocomplete.escapeRegex( value ),
				regExp = new RegExp(
					'((?:(?!' + escapedValue +').)*?)(' + escapedValue + ')(.*)', 'i'
				);

			this.menu.element.children().each( function( i ) {
				var itemLink = $( this ).find( 'a' );

				// only replace if suggestions actually starts with the current input
				if ( itemLink.text().toLowerCase().indexOf( value.toLowerCase() ) === 0 ) {
					var matches = itemLink.text().match( regExp );

					itemLink
					.text( matches[1] )
					.append( $( '<b/>' ).text( matches[2] ) )
					.append( document.createTextNode( matches[3] ) );
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
		},

		/**
		 * Completes the input box with the remaining characters of a given string. The characters
		 * of the remaining part are text-highlighted, so the will be overwritten if typing
		 * characters is continue. Tabbing or clicking outside of the input box will leave the
		 * completed string in the input box.
		 *
		 * @param incomplete {String}
		 * @param complete {String}
		 * @return {Number} number of characters added (and highlighted) at the end of the
		 *         incomplete string
		 */
		autocompleteString: function( incomplete, complete ) {
			if(
				// if nothing to complete, just return and don't move the cursor
				// (can be annoying in this situation)
				incomplete === complete
				// The following statement is a work-around for a technically unexpected search
				// behaviour: e.g. in English Wikipedia opensearch for "Allegro [...]" returns
				// "Allegro" as first result instead of "Allegro (music)", so auto-completion should
				// probably be prevented here since it would always reset the input box's value to
				// "Allegro"
				|| complete.toLowerCase().indexOf( this.element.val().toLowerCase() ) === -1
			) {
				return 0;
			}

			// set value to complete value...
			this.element.val( complete );

			// ... and select the suggested, not manually typed part of the value
			var start = incomplete.length,
				end = complete.length,
				node = this.element[0];

			// highlighting takes some browser specific implementation
			if( node.createTextRange ) { // opera < 10.5 and IE
				var selRange = node.createTextRange();
				selRange.collapse( true );
				selRange.moveStart( 'character', start);
				selRange.moveEnd( 'character', end);
				selRange.select();
			} else if( node.setSelectionRange ) { // major modern browsers
				// make a 'backward' selection so pressing arrow left won't put the cursor near the
				// selections end but rather at the typing position
				node.setSelectionRange( start, end, 'backward' );
			} else if( node.selectionStart ) {
				node.selectionStart = start;
				node.selectionEnd = end;
			}
			return ( end - start );
		}

	} );

} )( jQuery );

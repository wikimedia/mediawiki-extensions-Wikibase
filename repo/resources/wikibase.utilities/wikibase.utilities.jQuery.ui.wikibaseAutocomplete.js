/**
 * Wikibase extension enhancing jquery.ui.autocomplete
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */
( function( $ ) {
	'use strict';

	/**
	 * This widget adds a few enhancements to jquery.ui.autocomplete, e.g. adding a scrollbar when a
	 * certain number of items is listed in the suggestion list, highlighting matching characters
	 * in the suggestions and dealing with language direction.
	 * Specifying 'url' and 'ajaxParams' parameters will trigger using a custom function to handle
	 * the server response (_handleResponse()). Alternatively, an array may be passed as source or
	 * a completely custom function - both is covered by native jquery.ui.autocomplete
	 * functionality.
	 * See jquery.ui.autocomplete for further documentation - just listing additional options here.
	 *
	 * @example $( 'input' ).wikibaseAutocomplete( { source: ['a', 'b', 'c'] } );
	 * @desc Creates a simple auto-completion input element passing an array as result set.
	 *
	 * @example $( 'input' ).wikibaseAutocomplete( {
	 *   url: <url>,
	 *   ajaxParams: { <additional parameters> },
	 * } );
	 * @desc Creates an auto-completion input element fetching suggestions via AJAX.
	 *
	 * @option maxItems {Integer} (optional) If the number of suggestions is higher than maxItems,
	 *         the suggestion list will be made scrollable.
	 *         Default value: 10
	 *
	 * @option url {String} (optional) URL to fetch suggestions from (if these shall be queried via
	 *         AJAX)
	 *         Default value: null
	 *
	 * @option ajaxParams {Object} (optional) Additional AJAX parameters (if suggestions shall be
	 *         retrievend via AJAX)
	 *         Default value: null
	 *
	 * @option timeout {Integer} (optional) AJAX timeout in milliseconds.
	 *         Default value: 8000
	 */
	$.widget( 'wb.autocomplete', $.ui.autocomplete, {

		// additional options
		options: {
			maxItems: 10,
			url: null,
			ajaxParams: null,
			timeout: 8000
		},

		/**
		 * Caching the last pressed key's code
		 * @var {Integer}
		 */
		_lastKeyDown: null,

		/**
		 * @see jquery.ui.autocomplete._create
		 */
		_create: function() {
			if ( this.options.source === null && this.options.ajaxParams !== null ) {
				this.options.source = this._handleResponse;
			}

			$.ui.autocomplete.prototype._create.call( this );

			this.element.on( 'autocompleteopen', $.proxy( function( event ) {
				this._updateDirection();
				this._highlightMatchingCharacters();
			}, this ) );

			this.element.on( 'keydown', $.proxy( function( event ) {
				this._lastKeyDown = event.keyCode;
			}, this ) );

			this.element.on( 'close', $.proxy( function( event, ui ) {
				this.element.removeClass( 'ui-autocomplete-loading' );
			}, this ) );

			// since results list does not reposition automatically on resize, just close it
			// (one resize event handler is enough for all widgets)
			$( window ).off( 'wikibaseAutocomplete' );
			$( window ).on( 'resize.wikibaseAutocomplete', $.proxy( function() {
				if ( $( '.ui-autocomplete-input' ).length > 0 ) {
					$( '.ui-autocomplete-input' ).data( 'autocomplete' ).close( {} );
				}
			}, this ) );
		},

		/**
		 * Handles AJAX response filling auto-complete result set on success.
		 *
		 * @param request {Object} Contains request parameters
		 * @param suggest {Function} Callback putting results into auto-complete menu
		 */
		_handleResponse: function( request, suggest ) {
			$.ajax( {
				url: this.options.url,
				dataType: 'jsonp',
				data:  $.extend( {}, this.options.ajaxParams, { 'search': request.term } ),
				timeout: this.options.timeout,
				success: $.proxy( function( response ) {
					if ( response[0] === this.element.val() ) {
						suggest( response[1] ); // pass array of returned values to callback

						// auto-complete input box text (because of the API call lag, this is
						// avoided when hitting backspace, since the value would be reset too slow)
						if ( this._lastKeyDown !== 8 && response[1].length > 0 ) {
							this.autocompleteString(
								response[0],
								response[1][0]
							);
						}
						this._trigger( 'response.wikibase', [response[1]] );
					}
				}, this ),
				error: $.proxy( function( jqXHR, textStatus, errorThrown ) {
					this.element.removeClass( 'ui-autocomplete-loading' );
					this.element.focus();
					this._trigger( 'error.wikibase', [textStatus, errorThrown] );
				}, this )
			} );
		},

		/**
		 * Resizes the menu's height to the height of maximum list items.
		 *
		 * @see jquery.ui.autocomplete._resizeMenu
		 */
		_resizeMenu: function() {
			$.ui.autocomplete.prototype._resizeMenu.call( this );

			var menu = this.menu.element;
			menu.css( 'minWidth', 'auto' );
			if ( menu.children().length > this.options.maxItems ) {
				var fixedHeight = 0;
				for ( var i = 0; i < this.options.maxItems; i++ ) {
					fixedHeight += $( menu.children()[i] ).height();
				}
				menu.width( menu.width() + this._getScrollbarWidth() );
				menu.height( fixedHeight );
				menu.css( 'overflowY', 'scroll' );
			} else {
				menu.width( 'auto' );
				menu.height( 'auto' );
				menu.css( 'overflowY', 'auto' );
			}
			menu.css(
				'minWidth',
				this.element.outerWidth() - ( menu.outerWidth() - menu.width() ) + 'px'
			);
		},

		/**
		 * Calculates the width of the browser's scrollbar.
		 *
		 * @returns {Integer} scrollbar width
		 */
		_getScrollbarWidth: function() {
			var $inner = $( '<p/>', {
				style: 'width:100px'
			} ),
				$outer = $( '<div/>', {
					style: 'position:absolute;top:-1000px;left:-1000px;visibility:hidden;width:50px;height:50px;overflow:hidden;'
				} ).append( $inner ).appendTo( $( 'body' ) );
			var majorWidth = $inner.width();
			$outer.css( 'overflow', 'scroll' );
			var minorWidth = $inner.width();
			if ( majorWidth === minorWidth ) { // Webkit
				minorWidth = $outer[0].clientWidth;
			}
			$outer.remove();
			return ( majorWidth - minorWidth );
		},

		/**
		 * Makes autocomplete results list strech from the right side of the input box in rtl.
		 */
		_updateDirection: function() {
			if (
				this.element.attr( 'dir' ) === 'rtl' ||
					(
						typeof this.element.attr( 'dir' ) === 'undefined' &&
							document.documentElement.dir === 'rtl'
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
			var regexp = new RegExp(
				'(' + $.ui.autocomplete.escapeRegex( this.element.val() ) + ')', 'i'
			);
			this.menu.element.children().each( function( i ) {
				$( this ).find( 'a' ).html(
					$( this ).find( 'a' ).text().replace( regexp, '<b>$1</b>' )
				);
			} );
		},

		/**
		 * Completes the input box with the remaining characters of a given string. The characters
		 * of the remaining part are text-highlighted, so the will be overwritten if typing
		 * characers is continue. Tabbing or clicking outside of the input box will leave the
		 * completed string in the input box.
		 *
		 * @param incomplete {String}
		 * @param complete {String}
		 * @return {Integer} number of characters added (and highlighted) at the end of the incomplete string
		 */
		autocompleteString: function( incomplete, complete ) {
			if(
				// if nothing to complete, just return and don't move the curser (can be annoying in this situation)
				incomplete === complete
				// The following statement is a work-around for a technically unexpected search
				// behaviour: e.g. in English Wikipedia opensearch for "Allegro [...]" returns "Allegro"
				// as first result instead of "Allegro (music)", so auto-completion should probably be
				// prevented here since it would always reset the input box's value to "Allegro"
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
				// make a 'backward' selection so pressing arrow left won't put the cursor near the selections
				// end but rather at the typing position
				node.setSelectionRange( start, end, 'backward' );
			} else if( node.selectionStart ) {
				node.selectionStart = start;
				node.selectionEnd = end;
			}
			return ( end - start );
		}

	} );

	$.widget.bridge( 'wikibaseAutocomplete', $.wb.autocomplete );

} )( jQuery );

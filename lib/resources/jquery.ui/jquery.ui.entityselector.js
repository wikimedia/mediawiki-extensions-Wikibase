/**
 * Wikibase entity selector widget
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.2
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, undefined ) {
	'use strict';

	/**
	 * Entity selector
	 * Allows search for entities by typing into an input field encapsulating auto-complete
	 * functionality and offering suggestions in a list below the input box.
	 *
	 * @example $( 'input' ).entityselector( {
	 *   url: <url to retrieve results from>,
	 *   language: <language to fetch results in>
	 * } );
	 * @desc Creates a simple entity selector fetching items.
	 *
	 * @option url {String} URL to retrieve results from.
	 *
	 * @option language {String} code of the language results shall be fetched in.
	 *
	 * @option entityType {String} (optional) entity type that will be queried for results.
	 *         Default value: 'item'
	 *
	 * @option limit {Number} (optional) entity type that will be queried for results.
	 *         Default value: null (will pick limit specified server-side)
	 *
	 * @option maxItems {Number} (optional) If the number of suggestions is higher than maxItems,
	 *         the suggestion list will be made scrollable.
	 *         Default value: 10
	 *
	 * @option handles {String} (optional) Passed to jquery.ui.resizable, this parameter specifies
	 *         the borders where resize handles should be placed (e.g. 'e' stands for east). For rtl
	 *         languages, the parameter should be set to 'w'.
	 *         @see ui.resizable.options.handles
	 *         Default value: 'e'
	 *
	 * @option timeout {Number} (optional) AJAX timeout in milliseconds.
	 *         Default value: 8000
	 */
	$.widget( 'ui.entityselector', $.ui.suggester, {

		/**
		 * Options
		 * @type {Object}
		 */
		options: {
			url: null,
			language: null,
			entityType: 'item',
			limit: null,
			maxItems: 10,
			handles: 'e',
			timeout: 8000
		},

		/**
		 * Caching search term.
		 * @type {String}
		 */
		_term: null,

		/**
		 * Caching the offset of the result set's last fetched portion.
		 * @type {Number}
		 */
		_offset: 0,

		/**
		 * Hidden input element that will hold the actual entity id.
		 * @type {jQuery}
		 */
		_hiddenInput: null,

		/**
		 * @see ui.suggester._create
		 */
		_create: function() {
			if ( this.options.url === null ) {
				throw new Error( 'ui.entityselector requires url parameter to be specified.' );
			} else if ( this.options.language === null ) {
				throw new Error( 'ui.entityselector requires language parameter to be specified.' );
			}

			$.ui.suggester.prototype._create.call( this );

			this.element.addClass( 'ui-entityselector-input' );
			this.menu.element.addClass( 'ui-entityselector-list' );

			// construct a hidden input element which will contain the selected entity's id
			if ( this._hiddenInput === null ) {
				this._hiddenInput = $( '<input/>', {
					type: 'hidden',
					name: this.element.attr( 'name' )
				} ).insertAfter( this.element );
				this.element.removeAttr( 'name' );
			}

			var self = this;

			this.menu.element.on( 'menuselected', function( event, ui ) {
				self._hiddenInput.val( ui.item.data( 'item.autocomplete' ).label );
			} );

			$( this ).on( 'entityselectorresponse', function( event, response ) {
				self._offset = response.moreoffset;
			} );
		},

		/**
		 * @see ui.suggester.destroy
		 */
		destroy: function() {
			this.element.removeClass( 'ui-entityselector-input' );
			this._hiddenInput.remove();
			this._hiddenInput = null;
			this._term = null;
			this._offest = 0;
			$.ui.autocomplete.prototype.destroy.call( this );
		},

		/**
		 * @see ui.suggester._request
		 */
		_request: function( request, suggest ) {
			this._term = request.term;
			$.extend( this.options.ajax, {
				url: this.options.url,
				timeout: this.options.timeout,
				params: {
					action: 'wbsearchentities',
					format: 'json',
					language: this.options.language,
					entityType: this.options.entityType
				}
			} );
			if ( this.options.limit !== null ) {
				this.options.ajax.params.limit = this.options.limit;
			}
			$.ui.suggester.prototype._request.apply( this, arguments );
		},

		/**
		 * @see ui.suggester._success
		 */
		_success: function( response ) {
			// check if response has all information
			if ( response.success !== undefined ) {
				var suggest = this._response();
				if ( response.searchinfo.search === this.element.val() ) {
					suggest( response.search ); // pass array of returned values to callback

					// auto-complete input box text (because of the API call lag, this is
					// avoided when hitting backspace, since the value would be reset too slow)
					if ( this._lastKeyDown !== 8 && response.search.length > 0 ) {
						var stringToAutocomplete = response.search[0].label;

						// Aliases array is returned only when there is a search hit on an alias.
						// If the label does not start with the search string, auto-complete the
						// alias.
						if ( response.search[0].aliases.length > 0
							&& response.search[0].label.toLowerCase().indexOf(
								response.searchinfo.search.toLowerCase()
							) !== 0
						) {
							stringToAutocomplete = response.search[0].aliases[0];
						}

						this.autocompleteString(
							response.searchinfo.search,
							stringToAutocomplete
						);
					}

					this.menu.element.children( 'li' ).first().addClass( 'first' );
					this._trigger( 'response', 0, [response.search] );
				}
			}
		},

		/**
		 * @see ui.autocomplete._resizeMenu
		 */
		_resizeMenu: function() {
			// the menu is rebuilt completely; therefore, jquery.ui.resizable has to be re-inited
			if ( this.menu.element.data( 'resizable' ) !== undefined ) {
				this.menu.element.data( 'resizable' ).destroy();
			}
			this.menu.element.resizable( { handles: this.options.handles } );
			$.ui.suggester.prototype._resizeMenu.call( this );
		},

		/**
		 * @see ui.autocomplete._renderItem
		 */
		_renderItem: function( ul, item ) {
			/* wrap all text in <a> tags using common jquery.menu style */
			var section =
				$( '<li/>' ).data( 'item.autocomplete', item )
				.append(
					$( '<a/>' ).addClass( 'ui-entityselector-section-container' ).append(
						$( '<span/>' ).addClass( 'ui-entityselector-label' ).text( item.label )
					)
				);

			if ( item.description !== undefined ) {
				section.children( '.ui-entityselector-section-container' ).append(
					$( '<span/>' ).addClass( 'ui-entityselector-description' )
					.text( item.description )
				);
			}

			if ( item.aliases.length > 0 ) {
				section.children( '.ui-entityselector-section-container' ).append(
					$( '<span/>' ).addClass( 'ui-entityselector-aliases' )
					.text( 'Also known as: ' + item.aliases.join( ', ' ) )
				);
			}

			return section.appendTo( ul );
		},

		/**
		 * Prevent highlighting of characters.
		 *
		 * @see ui.suggester._highlightMatchingCharacters
		 */
		_highlightMatchingCharacters: function() {}

	} );

} )( jQuery );

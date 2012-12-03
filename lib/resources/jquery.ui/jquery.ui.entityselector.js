/**
 * Wikibase entity selector
 * Allows searching for entities by typing into an input field encapsulating auto-complete
 * functionality and offering suggestions in a list below the input box.
 * @since 0.2
 * @file
 * @ingroup WikibaseLib
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
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
 * @option type {String} (optional) entity type that will be queried for results.
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
 *
 * @event response Triggered after an API request has been received successfully.
 *        Parameters: (1) {jQuery.Event}
 *                    (2) {Array} Entity data
 *
 * @dependency jquery.eachchange
 * @dependency jquery.ui.resizable
 */
( function( $, undefined ) {
	'use strict';


	$.widget( 'ui.entityselector', $.ui.suggester, {

		/**
		 * Options
		 * @type {Object}
		 */
		options: {
			url: null,
			language: null,
			type: 'item',
			limit: null,
			maxItems: 10,
			handles: 'e',
			timeout: 8000
		},

		/**
		 * Caching the most current entity returned from the API.
		 * @type {Object}
		 */
		_selectedEntity: null,

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

			var self = this;

			// Init the hidden input field upon creation, but the source element might not be in the
			// DOM during initialization. Therefore, _initHiddenInput() is triggered while receiving
			// input as well
			this._initHiddenInput();

			this.element
			.eachchange( function( event, oldVal ) {
				self._initHiddenInput();
				self._updateValue();
			} )
			.on( 'keydown.' + this.widgetName, function( event ) {
				// when pressing enter, check if the current input matches any of the suggested item
				// and select it
				if ( event.keyCode === $.ui.keyCode.ENTER ) {
					self._validateInput();
					self.element.val( self.element.val() ); // reset text selection
				}
			} )
			.on( 'blur.' + this.widgetName, function( event ) {
				self._validateInput();
			} );

			this.menu.element.on( 'menuselected.' + this.widgetName, function( event, ui ) {
				self._initHiddenInput();
				var item = ui.item.data( 'item.autocomplete' );
				self._hiddenInput.val( item.id );
				self.selectedEntity( item );
			} );

			$( this ).on( 'entityselectorresponse.' + this.widgetName, function( event, response ) {
				self._offset = response.moreoffset;
			} );
		},

		/**
		 * @see ui.suggester.destroy
		 */
		destroy: function() {
			this.menu.element.off( '.' + this.widgetName );

			this.element.off( '.' + this.widgetName );
			this.element.off( 'eachchange' );

			this.element.removeClass( 'ui-entityselector-input' );

			if( this._hiddenInput ) {
				this._hiddenInput.remove();
				this._hiddenInput = null;
			}
			this._term = null;
			this._offest = 0;
			$.ui.suggester.prototype.destroy.call( this );
		},

		/**
		 * Constructs a hidden input element which will contain the selected entity's id.
		 */
		_initHiddenInput: function() {
			if ( this._hiddenInput === null ) {
				this._hiddenInput = $( '<input/>', {
					type: 'hidden',
					name: this.element.attr( 'name' )
				} ).insertAfter( this.element );
				this.element.removeAttr( 'name' );
			}
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
					type: this.options.type
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

					this._updateValue();

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
				} else {
					// suggest nothing when the response does not match with the current input value
					// informing autocomplete that there is one less pending request
					suggest();
				}
			}
		},

		/**
		 * Tries to select an entity according to the input box value.
		 *
		 * @return {Boolean} Whether current input value could be matched to an entity label
		 */
		_validateInput: function() {
			var self = this,
				found = false;
			$.each( this.menu.element.children(), function( i, listItem ) {
				var item = $( listItem ).data( 'item.autocomplete' );
				if ( self.element.val() === item.label ) {
					self._hiddenInput.val( item.id );
					self.selectedEntity( item );
					self.close();
					found = true;
					return false;
				}
			} );
			if ( !found ) {
				this._hiddenInput.removeAttr( 'value' );
			}
			return found;
		},

		/**
		 * Checks whether the value specified in the input box matches the first suggested item.
		 * If not, the user changed the input value after selecting an item, so the actual value
		 * needs to be reset.
		 *
		 * @return {Boolean} Whether the value has been updated or not.
		 */
		_updateValue: function() {
			var items = this.menu.element.children( 'li' );
			if ( items.length > 0 ) {
				var firstItem = items.first().data( 'item.autocomplete' ),
					currentValue = this.element.val();
				if (
					currentValue === firstItem.label
					|| ( firstItem.aliases.length && currentValue === firstItem.aliases[0] )
				) {
					this.menu.activate( $.Event( 'programmatic' ), items.first() );
					this.selectedEntity( firstItem );
					this.menu.select( $.Event( 'programmatic' ) );
					this.menu.element.show(); // prevent hiding the menu
				} else {
					this._hiddenInput.removeAttr( 'value' );
					this.selectedEntity( null );
				}
				return true;
			}
			return false;
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
						$( '<span/>' ).addClass( 'ui-entityselector-itemcontent' )
						.append(
							$( '<span/>' ).addClass( 'ui-entityselector-label' ).text( item.label )
						)
					)
				);

			if ( item.description !== undefined ) {
				section.find( '.ui-entityselector-itemcontent' ).append(
					$( '<span/>' ).addClass( 'ui-entityselector-description' )
					.text( item.description )
				);
			}

			if ( item.aliases.length > 0 ) {
				section.find( '.ui-entityselector-itemcontent' ).append(
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
		_highlightMatchingCharacters: function() {},

		/**
		 * Sets/gets the currently selected entity.
		 *
		 * @param {Object} [entityData]
		 * @return {Object} entityData
		 */
		selectedEntity: function( entityData ) {
			if ( entityData !== undefined ) {
				this._selectedEntity = entityData;
			}
			return this._selectedEntity;
		}

	} );

}( jQuery ) );

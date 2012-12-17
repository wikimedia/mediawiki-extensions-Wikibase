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
 * @dependency query.ui.suggester
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
		$hiddenInput: null,

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

			// init hidden input field that shall transmit the entity id
			this._hiddenInput( '' );

			var self = this;

			this.element
			.eachchange( function( event, oldVal ) {
				self._updateValue();
			} )
			.on( 'keydown.' + this.widgetName, function( event ) {
				// when pressing enter, check if the current input matches any of the suggested item
				// and select it
				if ( event.keyCode === $.ui.keyCode.ENTER ) {
					if ( self.validateInput() ) {
						self.menu.select( $.Event( 'programmatic' ) );
					}
					self.element.val( self.element.val() ); // reset text selection
				}
			} )
			.on( 'blur.' + this.widgetName, function( event ) {
				if ( self.validateInput() ) {
					self._trigger(
						'select',
						0,
						{ item: self.menu.active.data( 'item.autocomplete' ) }
					);
				}
			} );

			// Prevent native menu selected callback to alter the input value when the "selected"
			// event is triggered programmatically from inside the entity selector.
			var nativeMenuSelectedFn = this.menu.option( 'selected' );
			this.menu.option( 'selected', function( event, ui ) {
				var value = self.element.val(),
					item = ui.item.data( 'item.autocomplete' );

				nativeMenuSelectedFn( event, ui );

				// Reset the input value when the event has been triggered programmatically
				// (e.g. do not replace the input value when matching on an alias)
				if ( event.originalEvent.type !== 'programmatic' ) {
					self.element.val( item.value );
				} else {
					self.element.val( value );
				}

				self._hiddenInput( item.id );
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

			if( this.$hiddenInput ) {
				this.$hiddenInput.remove();
				this.$hiddenInput = null;
			}
			this._term = null;
			this._offest = 0;
			$.ui.suggester.prototype.destroy.call( this );
		},

		/**
		 * Sets/gets the value of the hidden input element which will contain the selected entity's
		 * id.
		 *
		 * @param {String} [value]
		 * @return {String} Current value
		 */
		_hiddenInput: function( value ) {
			if ( this.$hiddenInput === null ) {
				this.$hiddenInput = $( '<input/>', {
					type: 'hidden',
					name: this.element.attr( 'name' )
				} ).insertAfter( this.element );
				this.element.removeAttr( 'name' );
			}
			if ( value !== undefined ) {
				this.$hiddenInput.val( value );
			}
			return this.$hiddenInput.val();
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
				var suggest = this._response(),
					searchTerm = response.searchinfo.search;

				if ( searchTerm === this.element.val() ) {
					for ( var i in response.search ) {
						// If the entity has no label, the search match has to be on an alias which
						// can be used as label as long as there is no dedicated label.
						if ( response.search[i].label ) {
							response.search[i].value = response.search[i].label;
						} else {
							response.search[i].value = response.search[i].id;
						}
					}

					suggest( response.search ); // pass array of returned values to callback

					this._updateValue();

					// auto-complete input box text (because of the API call lag, this is
					// avoided when hitting backspace, since the value would be reset too slow)
					if ( this._lastKeyDown !== 8 && response.search.length > 0 ) {
						var firstMatch = response.search[0],
							stringToAutocomplete = firstMatch.label || '';

						// Aliases array is returned only when there is a search hit on an alias.
						// If the label does not start with the search string, auto-complete the
						// alias.
						if ( firstMatch.aliases
							&& (
								!firstMatch.label // consider label not set at all
								|| firstMatch.label.toLowerCase().indexOf(
									searchTerm.toLowerCase()
								) !== 0
							)
						) {
							stringToAutocomplete = firstMatch.aliases[0];
						}

						this.autocompleteString(
							searchTerm,
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
					||
					( firstItem.aliases && currentValue === firstItem.aliases[0] )
				) {
					this.menu.activate( $.Event( 'programmatic' ), items.first() );
					this.selectedEntity( firstItem );
					this.menu.select( $.Event( 'programmatic' ) );
					// prevent hiding the menu if there is more than one suggestion remaining or the
					// last remaining suggestion has not been fully specified yet
					if (
						items.length > 1
						|| (
							currentValue !== firstItem.label
							&& currentValue !== firstItem.aliases[0]
						)
					) {
						this.menu.element.show();
					}
				} else {
					this._hiddenInput( '' );
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

			if ( item.aliases ) {
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
		 * Tries to select an entity according to the input box value.
		 *
		 * @return {Boolean} Whether current input value could be matched to an entity label
		 */
		validateInput: function() {
			var self = this,
				found = false;
			$.each( this.menu.element.children( 'li' ), function( i, listItem ) {
				var item = $( listItem ).data( 'item.autocomplete' );
				if ( self.element.val() === item.label ) {
					// no need to trigger selecting again
					if ( self.selectedEntity() !== null && item.id === self.selectedEntity().id ) {
						return false;
					} else {
						self._hiddenInput( item.id );
						self.selectedEntity( item );
						self.menu.activate( $.Event( 'programmatic' ), $( listItem ).first() );
						found = true;
						return false;
					}
				}
			} );
			if ( !found ) {
				this._hiddenInput( '' );
			}
			return found;
		},

		/**
		 * Sets/gets the currently selected entity.
		 *
		 * @param {Object} [entityData]
		 * @return {Object} entityData
		 */
		selectedEntity: function( entityData ) {
			if ( entityData !== undefined ) {
				this._selectedEntity = entityData;
				// TODO: make sure the selected entity is displayed!
			}
			return this._selectedEntity;
		}

	} );

}( jQuery ) );

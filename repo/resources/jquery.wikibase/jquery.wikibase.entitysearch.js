/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function () {
	'use strict';

	var PARENT = $.wikibase.entityselector;

	/**
	 * Entity selector widget enhanced to be used as global search element.
	 *
	 * @extends jQuery.wikibase.entityselector
	 *
	 * @option {jQuery.ui.ooMenu.CustomItem} [suggestionsPlaceholder]
	 *         Suggestions list item to be displayed while suggestions are retrieved.
	 */
	$.widget( 'wikibase.entitysearch', PARENT, {

		/**
		 * @see jQuery.wikibase.entityselector.options
		 */
		options: {
			suggestionsPlaceholder: null
		},

		/**
		 * @see jQuery.wikibase.entityselector._create
		 */
		_create: function () {
			var self = this;

			PARENT.prototype._create.call( this );

			this.element
			.on( 'eachchange.' + this.widgetName, function () {
				var menu = self.options.menu;
				if (
					self.options.suggestionsPlaceholder
					// TODO: Store visibility in model
					// eslint-disable-next-line no-jquery/no-sizzle
					&& ( !menu.option( 'items' ).length || !menu.element.is( ':visible' ) )
				) {
					self.options.suggestionsPlaceholder.setVisibility( true );
					// Early update required for the suggestionsPlaceholder's visibility
					self._term = self.element.val();
					self._updateMenu( [] );
				}
			} );
		},

		/**
		 * @see jQuery.wikibase.entityselector._createMenuItemFromSuggestion
		 * @protected
		 *
		 * @param {Object} entityStub
		 * @return {jQuery.wikibase.entityselector.Item}
		 */
		_createMenuItemFromSuggestion: function ( entityStub ) {
			var $label = this._createLabelFromSuggestion( entityStub ),
				value = entityStub.label || entityStub.id;

			return new PARENT.Item( $label, value, entityStub );
		},

		/**
		 * @see jQuery.ui.suggester._setOption
		 */
		_setOption: function ( key, value ) {
			if ( key === 'suggestionsPlaceholder' ) {
				var customItems = this.options.menu.option( 'customItems' );

				customItems.splice( customItems.indexOf( this.options.suggestionsPlaceholder ), 1 );

				if ( value instanceof $.ui.ooMenu.CustomItem ) {
					customItems.unshift( value );
				}

				this._close();
			}
			return PARENT.prototype._setOption.apply( this, arguments );
		},

		/**
		 * @see jQuery.wikibase.entityselector._initMenu
		 */
		_initMenu: function ( ooMenu ) {
			PARENT.prototype._initMenu.apply( this, arguments );

			if ( this.options.suggestionsPlaceholder ) {
				ooMenu.option( 'customItems' ).unshift( this.options.suggestionsPlaceholder );
			}

			ooMenu.element.addClass( 'wikibase-entitysearch-list' );

			$( ooMenu )
			.off( 'selected' )
			.on( 'selected.entitysearch', function ( event, item ) {
				if ( event.originalEvent
					&& /^key/.test( event.originalEvent.type )
					&& !( item instanceof $.ui.ooMenu.CustomItem )
				) {
					window.location.href = item.getEntityStub().url;
				}
			} );

			return ooMenu;
		},

		/**
		 * @see jQuery.ui.suggester._updateMenuVisibility
		 */
		_updateMenuVisibility: function () {
			if ( this._term ) {
				this._open();
				this.repositionMenu();
			} else {
				this._close();
			}
		},

		/**
		 * @see jQuery.wikibase.entityselector._getSuggestions
		 */
		_getSuggestions: function ( term ) {
			var self = this,
				promise = PARENT.prototype._getSuggestions.call( this, term );

			return promise.done( function ( suggestions, searchTerm ) {
				if ( self.options.suggestionsPlaceholder ) {
					self.options.suggestionsPlaceholder.setVisibility( false );
				}
			} );
		}

	} );

}() );

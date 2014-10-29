/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki at snater.com >
 */
( function( $, util ) {
	'use strict';

	/**
	 * Site selector
	 * Enhances an input box with auto-complete and auto-suggestion functionality for site ids.
	 * @since 0.2
	 *
	 * @example $( 'input' ).siteselector( { source: <{wikibase.Site[]}> } );
	 *
	 * @option {wikibase.Site[]|Function} source
	 *         An array of Site objects that shall be used to provide suggestions. Alternatively, a
	 *         function dynamically retrieving an array of Site objects may be provided.
	 *
	 * @option {number} [delay]
	 *         Delay in milliseconds of the request querying for suggestions.
	 *         Default: 0
	 *
	 * @event selected
	 *        Triggered whenever a site is selected or de-selected.
	 *        (1) {jQuery.Event}
	 *        (2) {string|null}
	 */
	$.widget( 'wikibase.siteselector', $.ui.suggester, {
		/**
		 * @see jQuery.ui.suggester.options
		 */
		options: {
			delay: 0
		},

		/**
		 * @type {wikibase.Site}
		 */
		_selectedSite: null,

		/**
		 * @see jQuery.ui.suggester._create
		 */
		_create: function() {
			var self = this;

			$.ui.suggester.prototype._create.apply( this, arguments );

			this.element
			.on( 'keydown.' + this.widgetName, function( event ) {
				if( event.keyCode === $.ui.keyCode.TAB ) {
					$( self.options.menu )
					.one( 'selected', function( event, item ) {
						self.element.val( item.getValue() );
					} );
				}
			} )
			.on( 'eachchange.' + this.widgetName, function( event, previousValue ) {
				self._selectedSite = null;
				self._term = self.element.val();

				clearTimeout( self.__searching );
				self.search()
				.done( function( suggestions ) {
					if( self.options.menu.element.is( ':visible' ) ) {
						self._selectFirstSite();
					} else {
						self._trigger( 'selected', null, [null] );
					}
				} );
			} )
			.on( 'siteselectoropen.' + this.widgetName, function() {
				self._selectFirstSite();
			} );
		},

		/**
		 * @see jQuery.ui.suggester.destroy
		 */
		destroy: function() {
			$( this.options.menu ).off( 'siteselector' );
			$.ui.suggester.prototype.destroy.call( this );
		},

		/**
		 * Implicitly selects the first site from the suggested sites.
		 */
		_selectFirstSite: function() {
			var menu = this.options.menu,
				menuItems = menu.option( 'items' ),
				site = null;

			if( menuItems.length > 0 && menu.element.is( ':visible' ) ) {
				this.options.menu.activate( menuItems[0] );
				site = menuItems[0].getSite();
			}

			if( this._selectedSite !== site ) {
				this._selectedSite = site;
				this._trigger(
					'selected',
					null,
					site ? [site.getId()] : [null]
				);
			}
		},

		/**
		 * @see jQuery.ui.suggester._initMenu
		 */
		_initMenu: function( ooMenu ) {
			var self = this;

			$.ui.suggester.prototype._initMenu.apply( this, arguments );

			this.options.menu.element.addClass( 'wikibase-siteselector-list' );

			$( this.options.menu )
			.on( 'selected.siteselector', function( event, item ) {
				if( item instanceof $.wikibase.siteselector.Item ) {
					self._selectedSite  = item.getSite();
					self.element.val( self._createItemValue( self._selectedSite ) );
					self._trigger( 'selected', null, [self._selectedSite.getId()] );
				}
			} )
			.on( 'blur.siteselector', function() {
				if( self._selectedSite ) {
					self.element.val( self._createItemValue( self._selectedSite ) );
				} else if( self.element.val() !== '' ) {
					self._selectFirstSite();
				}
			} );

			this.options.menu.element
			.on( 'mouseleave', function() {
				if( self.options.menu.element.is( ':visible' ) ) {
					self._selectedSite = null;
					self._selectFirstSite();
				}
			} );

			return ooMenu;
		},

		/**
		 * @see jQuery.ui.suggester._move
		 */
		_move: function( direction, activeItem, allItems ) {
			$.ui.suggester.prototype._move.apply( this, arguments );
			if( this._selectedSite === this.options.menu.getActiveItem().getSite() ) {
				this.element.val( this._term );
			}
		},

		/**
		 * @see jQuery.ui.suggester._moveOffEdge
		 */
		_moveOffEdge: function( direction ) {
			if( direction === 'previous' ) {
				var menu = this.options.menu,
					items = menu.option( 'items' );
				menu.activate( items[items.length - 1] );
				this.element.val( items[items.length - 1].getValue() );
			} else {
				$.ui.suggester.prototype._moveOffEdge.apply( this, arguments );
				this._selectedSite = null;
				this._selectFirstSite();
			}
		},

		/**
		 * @see jQuery.ui.suggester._getSuggestions
		 */
		_getSuggestions: function( term ) {
			var source = $.isFunction( this.options.source )
				? this.options.source()
				: this.options.source;

			return this._getSuggestionsFromArray( term, source );
		},

		/**
		 * @see jQuery.ui.suggester._getSuggestionsFromArray
		 */
		_getSuggestionsFromArray: function( term, source ) {
			var self = this,
				deferred = $.Deferred();

			if( term === '' ) {
				return deferred.resolve( [], term ).promise;
			}

			var suggestedSites = $.grep( source, function( site ) {
				var check = [
					site.getId(),
					site.getShortName(),
					site.getName(),
					site.getShortName() + ' (' + site.getId() + ')'
				];

				for( var i = 0; i < check.length; i++ ) {
					if( check[i].toLowerCase().indexOf( self._term.toLowerCase() ) === 0 ) {
						return true;
					}
				}

				return false;
			} );

			return deferred.resolve( suggestedSites, term ).promise();
		},

		/**
		 * @see jQuery.ui.suggester._createMenuItemFromSuggestion
		 */
		_createMenuItemFromSuggestion: function( suggestion, requestTerm ) {
			return new $.wikibase.siteselector.Item(
				this._createItemLabel( suggestion, requestTerm ),
				this._createItemValue( suggestion ),
				suggestion
			);
		},

		/**
		 * Creates the label of a suggestion item.
		 *
		 * @param {wikibase.Site} site
		 * @return {string}
		 */
		_createItemLabel: function( site, requestTerm ) {
			return util.highlightSubstring( requestTerm, site.getShortName(), {
				caseInsensitive: true
			} )
			+ ' (' + util.highlightSubstring( requestTerm, site.getId(), {
				caseInsensitive: true
			} ) + ')';
		},

		/**
		 * Creates the value of a suggestion item.
		 *
		 * @param {wikibase.Site} site
		 * @return {string}
		 */
		_createItemValue: function( site ) {
			return site.getShortName() + ' (' + site.getId() + ')';
		},

		/**
		 * Returns the currently selected site.
		 *
		 * @return {wikibase.Site|null}
		 */
		getSelectedSite: function() {
			return this._selectedSite;
		},

		/**
		 * Sets the selected site.
		 *
		 * @param {wikibase.Site} site
		 */
		setSelectedSite: function( site ) {
			this._selectedSite = site;
		}

	} );

/**
 * Default siteselector suggestion menu item.
 * @constructor
 * @extends jQuery.ui.ooMenu.Item
 *
 * @param {string|jQuery} label
 * @param {string} value
 * @param {wikibase.Site} site
 *
 * @throws {Error} if a required parameter is not specified.
 */
var Item = function( label, value, site ) {
	if( !label || !value || !site ) {
		throw new Error( 'Required parameter(s) not specified' );
	}

	this._label = label;
	this._value = value;
	this._site = site;
};

Item = util.inherit(
	$.ui.ooMenu.Item,
	Item,
	{
		/**
		 * @type {wikibase.Site}
		 */
		_site: null,

		/**
		 * @return {wikibase.Site}
		 */
		getSite: function() {
			return this._site;
		}
	}
);

$.extend( $.wikibase.siteselector, {
	Item: Item
} );

} )( jQuery, util );

/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki at snater.com >
 */
( function () {
	'use strict';

	require( '../jquery.ui/jquery.ui.suggester.js' );
	require( '../jquery.ui/jquery.ui.ooMenu.js' );

	/**
	 * Site selector
	 * Enhances an input box with auto-complete and auto-suggestion functionality for site ids.
	 *
	 * @example $( 'input' ).siteselector( { source: <{wikibase.Site[]}> } );
	 *
	 * @option {wikibase.Site[]|Function} source
	 *         An array of Site objects that shall be used to provide suggestions. Alternatively, a
	 *         function dynamically retrieving an array of Site objects may be provided.
	 *
	 * @option {number} [delay=150]
	 *         Delay in milliseconds of the request querying for suggestions.
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
			delay: 150
		},

		/**
		 * @type {wikibase.Site}
		 */
		_selectedSite: null,

		/**
		 * @see jQuery.ui.suggester._create
		 */
		_create: function () {
			var self = this;

			$.ui.suggester.prototype._create.apply( this, arguments );

			this.element
			.on( 'keydown.' + this.widgetName, function ( event ) {
				if ( event.keyCode === $.ui.keyCode.TAB ) {
					$( self.options.menu )
					.one( 'selected', function ( ev, item ) {
						self.element.val( item.getValue() );
					} );
				} else if ( event.keyCode === $.ui.keyCode.ESCAPE ) {
					var degrade = true,
						firstItem = self.options.menu.option( 'items' )[ 0 ];

					if ( firstItem ) {
						var site = firstItem.getSite(),
							label = self._createItemLabel( site, '' ),
							value = self._createItemValue( site );

						if ( self._term === label || self._term === value ) {
							degrade = false;
						}
					}

					if ( degrade ) {
						self.options.menu.deactivate();
						self.element.val( self._term );
						self._selectedSite = null;
						self._trigger( 'selected', null, [ null ] );
					}
				}
			} )
			.on( 'eachchange.' + this.widgetName, function ( event, previousValue ) {
				self._selectedSite = null;
				self._term = self.element.val();

				clearTimeout( self._searching );
				self._searching = setTimeout( function () {
					self.search()
					.done( function ( suggestions ) {
						// TODO: Store visibility in model
						// eslint-disable-next-line no-jquery/no-sizzle
						if ( self.options.menu.element.is( ':visible' ) ) {
							self._selectFirstSite();
						} else {
							self._trigger( 'selected', null, [ null ] );
						}
					} );
				}, self.options.delay );
			} )
			.on( 'siteselectoropen.' + this.widgetName, function () {
				self._selectFirstSite();
			} );
		},

		/**
		 * @see jQuery.ui.suggester.destroy
		 */
		destroy: function () {
			$( this.options.menu ).off( 'siteselector' );
			$.ui.suggester.prototype.destroy.call( this );
		},

		/**
		 * Implicitly selects the first site from the suggested sites.
		 */
		_selectFirstSite: function () {
			var menu = this.options.menu,
				menuItems = menu.option( 'items' ),
				site = null;

			// TODO: Store visibility in model
			// eslint-disable-next-line no-jquery/no-sizzle
			if ( menuItems.length > 0 && menu.element.is( ':visible' ) ) {
				this.options.menu.activate( menuItems[ 0 ] );
				site = menuItems[ 0 ].getSite();
			}

			if ( this._selectedSite !== site ) {
				this._selectedSite = site;
				this._trigger(
					'selected',
					null,
					site ? [ site.getId() ] : [ null ]
				);
			}
		},

		/**
		 * @see jQuery.ui.suggester._initMenu
		 */
		_initMenu: function ( ooMenu ) {
			var self = this;

			$.ui.suggester.prototype._initMenu.apply( this, arguments );

			this.options.menu.element.addClass( 'wikibase-siteselector-list' );

			$( this.options.menu )
			.on( 'selected.siteselector', function ( event, item ) {
				if ( item instanceof $.wikibase.siteselector.Item ) {
					self._selectedSite = item.getSite();
					self.element.val( self._createItemValue( self._selectedSite ) );
					self._trigger( 'selected', null, [ self._selectedSite.getId() ] );
				}
			} )
			.on( 'blur.siteselector', function () {
				if ( self._selectedSite ) {
					self.element.val( self._createItemValue( self._selectedSite ) );
				} else if ( self.element.val() !== '' ) {
					self._selectFirstSite();
				}
			} );

			this.options.menu.element
			.on( 'mouseleave', function () {
				// TODO: Store visibility in model
				// eslint-disable-next-line no-jquery/no-sizzle
				if ( self.options.menu.element.is( ':visible' ) ) {
					self._selectedSite = null;
					self._selectFirstSite();
				}
			} );

			return ooMenu;
		},

		/**
		 * @see jQuery.ui.suggester._move
		 */
		_move: function ( direction, activeItem, allItems ) {
			$.ui.suggester.prototype._move.apply( this, arguments );
			if ( this._selectedSite === this.options.menu.getActiveItem().getSite() ) {
				this.element.val( this._term );
			}
		},

		/**
		 * @see jQuery.ui.suggester._moveOffEdge
		 */
		_moveOffEdge: function ( direction ) {
			if ( direction === 'previous' ) {
				var menu = this.options.menu,
					items = menu.option( 'items' );
				menu.activate( items[ items.length - 1 ] );
				this.element.val( items[ items.length - 1 ].getValue() );
			} else {
				$.ui.suggester.prototype._moveOffEdge.apply( this, arguments );
				this._selectedSite = null;
				this._selectFirstSite();
			}
		},

		/**
		 * @see jQuery.ui.suggester._getSuggestions
		 */
		_getSuggestions: function ( term ) {
			var source = typeof this.options.source === 'function'
				? this.options.source()
				: this.options.source;

			return this._getSuggestionsFromArray( term, source );
		},

		/**
		 * @see jQuery.ui.suggester._getSuggestionsFromArray
		 */
		_getSuggestionsFromArray: function ( term, source ) {
			var self = this,
				deferred = $.Deferred();

			if ( term === '' ) {
				return deferred.resolve( [], term ).promise();
			}

			var suggestedSites = source.filter( function ( site ) {
				return self._considerSuggestion( site );
			} );

			if ( suggestedSites.length === 0 ) {
				var subDomain = this._grepSubDomainFromTerm();

				if ( subDomain ) {
					suggestedSites = source.filter( function ( site ) {
						var url = site.getUrlTo( '' ),
							index = url.indexOf( '//' ) + 2;

						return url.indexOf( subDomain, index ) === index;
					} );
				}
			}

			return deferred.resolve( suggestedSites, term ).promise();
		},

		/**
		 * @protected
		 *
		 * @param {wikibase.Site} site
		 * @return {boolean}
		 */
		_considerSuggestion: function ( site ) {
			var check = [
				site.getId(),
				site.getShortName(),
				site.getName(),
				site.getShortName() + ' (' + site.getId() + ')'
			];

			for ( var i = 0; i < check.length; i++ ) {
				if ( check[ i ].toLowerCase().indexOf( this._term.toLowerCase() ) === 0 ) {
					return true;
				}
			}

			return false;
		},

		/**
		 * @return {string|null}
		 * @private
		 */
		_grepSubDomainFromTerm: function () {
			// Extract either a subdomain (the word after "//") or simply the first word.
			var matches = /\/\/(\w[\w-]+)/.exec( this._term )
				|| /(\w[\w-]+)/.exec( this._term );

			return matches ? matches[ 1 ].toLowerCase().replace( /[\W_]+/g, '-' ) : null;
		},

		/**
		 * @see jQuery.ui.suggester._createMenuItemFromSuggestion
		 * @protected
		 *
		 * @param {wikibase.Site} site
		 * @param {string} requestTerm
		 * @return {jQuery.wikibase.siteselector.Item}
		 */
		_createMenuItemFromSuggestion: function ( site, requestTerm ) {
			return new $.wikibase.siteselector.Item(
				this._createItemLabel( site, requestTerm ),
				this._createItemValue( site ),
				site
			);
		},

		/**
		 * Creates the label of a suggestion item.
		 *
		 * @param {wikibase.Site} site
		 * @param {string} requestTerm
		 * @return {string}
		 */
		_createItemLabel: function ( site, requestTerm ) {
			var highlightSubstring = require( '../util.highlightSubstring.js' );
			return highlightSubstring( requestTerm, site.getShortName() )
			+ ' (' + highlightSubstring( requestTerm, site.getId() ) + ')';
		},

		/**
		 * Creates the value of a suggestion item.
		 *
		 * @param {wikibase.Site} site
		 * @return {string}
		 */
		_createItemValue: function ( site ) {
			return site.getId();
		},

		/**
		 * Returns the currently selected site.
		 *
		 * @return {wikibase.Site|null}
		 */
		getSelectedSite: function () {
			return this._selectedSite;
		}
	} );

	/**
	 * Default siteselector suggestion menu item.
	 *
	 * @constructor
	 * @extends jQuery.ui.ooMenu.Item
	 *
	 * @param {string|jQuery} label
	 * @param {string} value
	 * @param {wikibase.Site} site
	 *
	 * @throws {Error} if a required parameter is not specified.
	 */
	var Item = function ( label, value, site ) {
			if ( !label || !value || !site ) {
				throw new Error( 'Required parameter(s) not specified' );
			}

			this._label = label;
			this._value = value;
			this._site = site;
		},
		inherit = require( '../util.inherit.js' );

	Item = inherit(
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
			getSite: function () {
				return this._site;
			}
		}
	);

	$.extend( $.wikibase.siteselector, {
		Item: Item
	} );

}() );

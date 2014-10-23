/**
 * Wikibase entity selector
 * Enhances an input box with auto-complete and auto-suggestion functionality for Wikibase entities.
 * @since 0.2
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @example $( 'input' ).entityselector( {
 *   url: <{string} URL to the API of a MediaWiki instance running Wikibase repository>,
 *   language: <{string} language code of the language to fetch results in>
 * } );
 *
 * @option {string} url (REQUIRED)
 *         URL to retrieve results from.
 *
 * @option {string} language (REQUIRED, optional when in MediaWiki context)
 *         Code of the language results shall be fetched in.
 *         Default value: User language (when in MediaWiki context)
 *
 * @option {string} type
 *         Entity type that will be queried for results.
 *         Default value: 'item'
 *
 * @option {number|null} limit
 *         Number of results to query the API for.
 *         Default value: null (will pick limit specified server-side)
 *
 * @option {number} timeout
 *         Default AJAX timeout in milliseconds.
 *         Default value: 8000
 *
 * @option messages {Object}
 *         Strings used within the widget.
 *         Messages should be specified using mwMsgOrString(<resource loader module message key>,
 *         <fallback message>) in order to use the messages specified in the resource loader module
 *         (if loaded).
 *         - {string} messages['aliases-label']
 *           Label prepending the alias(es) if there is a search hit on at least one alias of an
 *           entity.
 *           Default value: 'also known as:'
 *         - {string} messages['more']
 *           Label of the link to display more suggestions.
 *           Default value: 'more'
 *
 * @event selected
 *        Triggered after having selected an entity.
 *        Parameters: (1) {jQuery.Event}
 *                    (2) {string} Entity id
 *
 * @dependency jQuery.event.special.eachchange
 * @dependency jQuery.ui.ooMenu
 * @dependency jQuery.ui.suggester
 */
( function( $, util, mw ) {
	'use strict';

	// TODO: Get rid of MediaWiki context detection by submitting a message provider as option.

	/**
	 * Whether loaded in MediaWiki context.
	 * @type {boolean}
	 */
	var IS_MW_CONTEXT = mw !== undefined && mw.msg;

	/**
	 * Whether actual entity selector resource loader module is loaded.
	 * @type {boolean}
	 */
	var IS_MODULE_LOADED = (
		IS_MW_CONTEXT
		&& $.inArray( 'jquery.wikibase.entityselector', mw.loader.getModuleNames() ) !== -1
	);

	/**
	 * Returns a message from the MediaWiki context if the entity selector module has been loaded.
	 * If it has not been loaded, the corresponding string defined in the options will be returned.
	 *
	 * @param {String} msgKey
	 * @param {String} string
	 * @return {String}
	 */
	function mwMsgOrString( msgKey, string ) {
		return IS_MODULE_LOADED ? mw.msg( msgKey ) : string;
	}

	$.widget( 'wikibase.entityselector', $.ui.suggester, {

		/**
		 * Options
		 * @type {Object}
		 */
		options: {
			url: null,
			language: ( IS_MW_CONTEXT ) ? mw.config.get( 'wgUserLanguage' ) : null,
			type: 'item',
			limit: null,
			timeout: 8000,
			messages: {
				'aliases-label': mwMsgOrString( 'wikibase-aliases-label', 'also known as:' ),
				'more': mwMsgOrString( 'wikibase-entityselector-more', 'more' )
			}
		},

		/**
		 * Caching the most current entity returned from the API.
		 * @type {Object}
		 */
		_selectedEntity: null,

		/**
		 * Caches retrieved results.
		 * @type {Object}
		 */
		_cache: null,

		/**
		 * @see ui.suggester._create
		 */
		_create: function() {
			var self = this;

			this._cache = {};

			if( !this.options.source ) {
				if( this.options.url === null ) {
					throw new Error( 'When not specifying a dedicated source, URL option needs to '
						+ 'be specified' );
				} else if( this.options.language === null ) {
					throw new Error( 'When not specifying a dedicated source, language option '
						+ 'needs to be specified.' );
				}
				this.options.source = this._initDefaultSource();
			} else if( !$.isFunction( this.options.source ) && !$.isArray( this.options.source ) ) {
				throw new Error( 'Source needs to be a function or an array' );
			}

			$.ui.suggester.prototype._create.call( this );

			this.element
				.addClass( 'ui-entityselector-input' )
				.prop( 'dir', $( document ).prop( 'dir' ) );

			this.options.menu.element.addClass( 'ui-entityselector-list' );

			this.element
			.on( 'eachchange.' + this.widgetName, function( event, previousValue ) {
				self._cache = {};

				self._select( null );

				clearTimeout( self.__searching );
				self.search( event )
				.done( function( suggestions, requestTerm ) {
					if( suggestions.length === 0 || self.element.val() !== requestTerm ) {
						return;
					}

					if( suggestions[0].label === requestTerm ) {
						self._select( suggestions[0] );
					}
				} );
			} );
		},

		/**
		 * @see jQuery.ui.suggester.destroy
		 */
		destroy: function() {
			this.element.removeClass( 'ui-entityselector-input' );

			this._cache = {};

			$.ui.suggester.prototype.destroy.call( this );
		},

		/**
		 * Create and return the data object for the api call.
		 * @param {string} term
		 * @return {object}
		 */
		_getData: function( term ) {
			return {
				action: 'wbsearchentities',
				search: term,
				format: 'json',
				language: this.options.language,
				type: this.options.type,
				'continue': this._cache[term] && this._cache[term].nextSuggestionOffset
				? this._cache[term].nextSuggestionOffset : 0
			};
		},

		/**
		 * Initializes the default source pointing the the "wbsearchentities" API module via the
		 * URL provided in the options.
		 *
		 * @return {Function}
		 */
		_initDefaultSource: function() {
			var self = this;

			return function( term ) {
				var deferred = $.Deferred(),
					data = self._getData( term );

				if( self.options.limit ) {
					$.extend( data, {
						limit: self.options.limit
					} );
				}

				$.ajax( {
					url: self.options.url,
					timeout: self.options.timeout,
					dataType: 'json',
					data: data
				} )
				.done( function( response ) {
					deferred.resolve(
						response.search,
						response.searchinfo.search,
						response['search-continue']
					);
				} )
				.fail( function( jqXHR, textStatus ) {
					// Since this is a JSONP request, this will always fail with a timeout...
					deferred.reject( textStatus );
				} );

				return deferred.promise();
			};
		},

		/**
		 * @see jQuery.ui.suggester._updateMenu
		 */
		_updateMenu: function( suggestions ) {
			var scrollTop = this.options.menu.element.scrollTop();

			$.ui.suggester.prototype._updateMenu.apply( this, arguments );

			this.options.menu.element.scrollTop( scrollTop );
		},

		/**
		 * Generates the label for a suggester entity.
		 *
		 * @param {Object} entityStub
		 * @return {jQuery}
		 */
		_createLabelFromSuggestion: function( entityStub ) {
			var $suggestion = $( '<span class="ui-entityselector-itemcontent"/>' );

			$suggestion.append(
					$( '<span class="ui-entityselector-label"/>' )
					.text( entityStub.label || entityStub.id )
				);

			if( entityStub.description ) {
				$suggestion.append(
					$( '<span class="ui-entityselector-description"/>' )
					.text( entityStub.description )
				);
			}

			if( entityStub.aliases ) {
				var aliasesText = this.options.messages['aliases-label']
					+ ' '
					+ entityStub.aliases.join( ', ' );

				$suggestion.append(
					$( '<span class="ui-entityselector-aliases"/>' ).text( aliasesText )
				);
			}

			return $suggestion;
		},

		/**
		 * @see jQuery.ui.suggester._createMenuItemFromSuggestion
		 */
		_createMenuItemFromSuggestion: function( suggestion ) {
			var $label = this._createLabelFromSuggestion( suggestion ),
				value = suggestion.label || suggestion.id;

			return new $.wikibase.entityselector.Item( $label, value, suggestion );
		},

		/**
		 * @see jQuery.ui.suggester._initMenu
		 */
		_initMenu: function( ooMenu ) {
			var self = this;

			$.ui.suggester.prototype._initMenu.apply( this, arguments );

			$( this.options.menu )
			.on( 'selected.entityselector', function( event, item ) {
				if( item instanceof $.wikibase.entityselector.Item ) {
					self._select( item.getEntityStub() );
				}
			} );

			var customItems = ooMenu.option( 'customItems' );
			customItems.unshift( new $.ui.ooMenu.CustomItem(
				this.options.messages.more,
				function() {
					return self._cache[self._term]
						&& self._cache[self._term].nextSuggestionOffset
						&& self._cache[self._term].nextSuggestionOffset > self._cache[self._term].suggestions.length - 1;
				},
				function() {
					self.search( $.Event( 'programmatic' ) );
				},
				'ui-entityselector-more'
			) );

			ooMenu._evaluateVisibility = function( customItem ) {
				if( customItem instanceof $.ui.ooMenu.CustomItem ) {
					return customItem.getVisibility( ooMenu );
				} else {
					return ooMenu._evaluateVisibility.apply( this, arguments );
				}
			};

			ooMenu.option( 'customItems', customItems );

			return ooMenu;
		},

		/**
		 * @see jQuery.ui.suggester._getSuggestions
		 */
		_getSuggestions: function( term ) {
			var self = this;

			return $.ui.suggester.prototype._getSuggestions.apply( this, arguments )
			.then( function( suggestions, searchTerm, nextSuggestionOffset ) {
				var deferred = $.Deferred();

				if( self._cache[searchTerm] ) {
					$.merge( self._cache[searchTerm].suggestions, suggestions );
					self._cache[searchTerm].nextSuggestionOffset = nextSuggestionOffset;
				} else {
					self._cache = {};
					self._cache[searchTerm] = {
						suggestions: suggestions,
						nextSuggestionOffset: nextSuggestionOffset
					};
				}

				deferred.resolve( self._cache[searchTerm].suggestions, searchTerm );
				return deferred.promise();
			} );
		},

		/**
		 * @see jQuery.ui.suggester._getSuggestionsFromArray
		 */
		_getSuggestionsFromArray: function( term, source ) {
			var deferred = $.Deferred(),
				matcher = new RegExp( this._escapeRegex( term ), 'i' );

			deferred.resolve( $.grep( source, function( item ) {
				if( item.aliases ) {
					for( var i = 0; i < item.aliases.length; i++ ) {
						if( matcher.test( item.aliases[i] ) ) {
							return true;
						}
					}
				}

				return matcher.test( item.label ) || matcher.test( item.id );
			} ), term );

			return deferred.promise();
		},

		/**
		 * Selects an entity.
		 *
		 * @param {Object} entityStub
		 */
		_select: function( entityStub ) {
			var id =  entityStub ? entityStub.id : '';
			this._selectedEntity = entityStub;
			if( id ) {
				this._trigger( 'selected', null, [id] );
			}
		},

		/**
		 * Gets the selected entity.
		 *
		 * @return {Object}
		 */
		selectedEntity: function() {
			// TODO: Implement setter.
			return this._selectedEntity;
		}
	} );


/**
 * Default entityselector suggestion menu item.
 * @constructor
 * @extends jQuery.ui.ooMenu.Item
 *
 * @param {jQuery|string} label
 * @param {string} value
 * @param {Object} entityStub
 *
 * @throws {Error} if a required parameter is not specified properly.
 */
var Item = function( label, value, entityStub ) {
	if( !label || !value || !entityStub ) {
		throw new Error( 'Required parameter(s) not specified properly' );
	}

	this._label = label;
	this._value = value;
	this._entityStub = entityStub;
	this._link = entityStub.url;
};

Item = util.inherit(
	$.ui.ooMenu.Item,
	Item,
	{
		/**
		 * @type {Object}
		 */
		_entityStub: null,

		/**
		 * @return {Object}
		 */
		getEntityStub: function() {
			return this._entityStub;
		}
	}
);

$.extend( $.wikibase.entityselector, {
	Item: Item
} );

}( jQuery, util, mediaWiki ) );

( function( $, util, mw ) {
	'use strict';

// TODO: Get rid of MediaWiki context detection by submitting a message provider as option.

/**
 * Whether loaded in MediaWiki context.
 * @property {boolean}
 * @ignore
 */
var IS_MW_CONTEXT = mw !== undefined && mw.msg;

/**
 * Whether actual `entityselector` resource loader module is loaded.
 * @property {boolean}
 * @ignore
 */
var IS_MODULE_LOADED = (
	IS_MW_CONTEXT
	&& $.inArray( 'jquery.wikibase.entityselector', mw.loader.getModuleNames() ) !== -1
);

/**
 * Returns a message from the MediaWiki context if the `entityselector` module has been loaded.
 * If it has not been loaded, the corresponding string defined in the options will be returned.
 *
 * @ignore
 *
 * @param {string} msgKey
 * @param {string} string
 * @return {string}
 */
function mwMsgOrString( msgKey, string ) {
	return IS_MODULE_LOADED ? mw.msg( msgKey ) : string;
}

/**
 * Enhances an input box with auto-complete and auto-suggestion functionality for Wikibase entities.
 *
 *     @example
 *     $( 'input' ).entityselector( {
 *         url: <{string} URL to the API of a MediaWiki instance running Wikibase repository>,
 *         language: <{string} language code of the language to fetch results in>
 *     } );
 *
 * @since 0.2
 * @class jQuery.wikibase.entityselector
 * @extends jQuery.ui.suggester
 * @uses jQuery.event.special.eachchange
 * @uses jQuery.ui.ooMenu
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {Object} options
 * @param {string} options.url
 *        URL to retrieve results from.
 * @param {string} options.language
 *        (optional when in MediaWiki context)
 *        Language code of the language results shall be fetched in.
 *        Defaults to the user language (`mw.config.get( 'wgUserLanguage' )` when in MediaWiki
 *        context.
 * @param {string} [options.type='item']
 *        `Entity` type that will be queried for results.
 * @param {number|null} [options.limit=null]
 *         Number of results to query the API for. Will pick limit specified server-side if ´null´.
 * @param {boolean} [options.caseSensitive=false]
 *        Whether the widget shall consider letter case when determining if the first suggestion
 *        matches with the current input triggering the "select" mechanism.
 * @param {number} [options.timeout=8000]
 *        Default AJAX timeout in milliseconds.
 * @param {Object} [options.messages=Object]
 *        Strings used within the widget.
 *        Messages should be specified using `mwMsgOrString(<resource loader module message key>,
 *        <fallback message>)` in order to use the messages specified in the resource loader module
 *        (if loaded).
 * @param {string} [options.messages.more='more']
 *        Label of the link to display more suggestions.
 */
/**
 * @event selected
 * Triggered after having selected an entity.
 * @param {jQuery.Event} event
 * @param {string} entityId
 */
$.widget( 'wikibase.entityselector', $.ui.suggester, {

	/**
	 * Options
	 * @property {Object}
	 */
	options: {
		url: null,
		language: ( IS_MW_CONTEXT ) ? mw.config.get( 'wgUserLanguage' ) : null,
		type: 'item',
		limit: null,
		caseSensitive: false,
		timeout: 8000,
		messages: {
			more: mwMsgOrString( 'wikibase-entityselector-more', 'more' )
		}
	},

	/**
	 * Caching the most current entity returned from the API.
	 * @property {Object}
	 * @private
	 */
	_selectedEntity: null,

	/**
	 * Caches retrieved results.
	 * @property {Object} [_cache={}]
	 * @private
	 */
	_cache: null,

	/**
	 * @inheritdoc
	 * @protected
	 */
	_create: function() {
		var self = this;

		this._cache = {};

		if ( !this.options.source ) {
			if ( this.options.url === null ) {
				throw new Error( 'When not specifying a dedicated source, URL option needs to be '
					+ 'specified' );
			} else if ( this.options.language === null ) {
				throw new Error( 'When not specifying a dedicated source, language option needs to '
					+ 'be specified.' );
			}
			this.options.source = this._initDefaultSource();
		} else if ( !$.isFunction( this.options.source ) && !$.isArray( this.options.source ) ) {
			throw new Error( 'Source needs to be a function or an array' );
		}

		$.ui.suggester.prototype._create.call( this );

		this.element
			.addClass( 'ui-entityselector-input' )
			.prop( 'dir', $( document ).prop( 'dir' ) );

		this.options.menu.element.addClass( 'ui-entityselector-list' );

		this.element
		.off( 'blur' )
		.on( 'eachchange.' + this.widgetName, function( event ) {
			self._search( event );
		} );
	},

	/**
	 * @inheritdoc
	 */
	destroy: function() {
		this.element.removeClass( 'ui-entityselector-input' );

		this._cache = {};

		$.ui.suggester.prototype.destroy.call( this );
	},

	/**
	 * @protected
	 *
	 * @param {jQuery.Event} event
	 */
	_search: function( event ) {
		var self = this;

		this._cache = {};
		this._select( null );

		clearTimeout( this._searching );
		this._searching = setTimeout( function() {
			self.search( event )
			.done( function( suggestions, requestTerm ) {
				if ( !suggestions.length || self.element.val() !== requestTerm ) {
					return;
				}

				if ( self._termMatchesSuggestion( requestTerm, suggestions[0] ) ) {
					self._select( suggestions[0] );
				}
			} );
		}, this.options.delay );
	},

	/**
	 * Determines whether a term matches a label considering the `caseSensitive` option.
	 *
	 * @protected
	 *
	 * @param {string} term
	 * @param {Object} suggestion
	 * @return {boolean}
	 */
	_termMatchesSuggestion: function( term, suggestion ) {
		var label = suggestion.label || suggestion.id;
		return label === term
			|| !this.options.caseSensitive && label.toLowerCase() === term.toLowerCase()
			|| term === suggestion.id;
	},

	/**
	 * Create and return the data object for the api call.
	 *
	 * @protected
	 *
	 * @param {string} term
	 * @return {Object}
	 */
	_getData: function( term ) {
		return {
			action: 'wbsearchentities',
			search: term,
			format: 'json',
			language: this.options.language,
			uselang: this.options.language,
			type: this.options.type,
			'continue': this._cache[term] && this._cache[term].nextSuggestionOffset
			? this._cache[term].nextSuggestionOffset : 0
		};
	},

	/**
	 * Initializes the default source pointing the the `wbsearchentities` API module via the URL
	 * provided in the options.
	 *
	 * @protected
	 *
	 * @return {Function}
	 */
	_initDefaultSource: function() {
		var self = this;

		return function( term ) {
			var deferred = $.Deferred(),
				data = self._getData( term );

			if ( self.options.limit ) {
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
				deferred.reject( textStatus );
			} );

			return deferred.promise();
		};
	},

	/**
	 * @inheritdoc
	 * @protected
	 */
	_updateMenu: function( suggestions ) {
		var scrollTop = this.options.menu.element.scrollTop();

		$.ui.suggester.prototype._updateMenu.apply( this, arguments );

		this.options.menu.element.scrollTop( scrollTop );
	},

	/**
	 * Generates the label for a suggester entity.
	 *
	 * @protected
	 *
	 * @param {Object} entityStub
	 * @return {jQuery}
	 */
	_createLabelFromSuggestion: function( entityStub ) {
		var $suggestion = $( '<span class="ui-entityselector-itemcontent"/>' ),
				$label = $( '<span class="ui-entityselector-label"/>' ).text( entityStub.label || entityStub.id );

		if ( entityStub.aliases ) {
			$label.append(
					$( '<span class="ui-entityselector-aliases"/>' ).text( ' (' + entityStub.aliases.join( ', ' ) +  ')' )
			);
		}

		$suggestion.append( $label );

		if ( entityStub.description ) {
			$suggestion.append(
				$( '<span class="ui-entityselector-description"/>' )
				.text( entityStub.description )
			);
		}

		return $suggestion;
	},

	/**
	 * @inheritdoc
	 * @protected
	 */
	_createMenuItemFromSuggestion: function( suggestion ) {
		var $label = this._createLabelFromSuggestion( suggestion ),
			value = suggestion.label || suggestion.id;

		return new $.wikibase.entityselector.Item( $label, value, suggestion );
	},

	/**
	 * @inheritdoc
	 * @protected
	 */
	_initMenu: function( ooMenu ) {
		var self = this;

		$.ui.suggester.prototype._initMenu.apply( this, arguments );

		$( this.options.menu )
		.off( 'selected.suggester' )
		.on( 'selected.entityselector', function( event, item ) {
			if ( item.getEntityStub ) {
				if ( !self.options.caseSensitive
					&& item.getValue().toLowerCase() === self._term.toLowerCase()
				) {
					self._term = item.getValue();
				} else {
					self.element.val( item.getValue() );
				}

				self._close();
				self._trigger( 'change' );

				var entityStub = item.getEntityStub();

				if ( !self._selectedEntity || entityStub.id !== self._selectedEntity.id ) {
					self._select( entityStub );
				}
			}
		} );

		var customItems = ooMenu.option( 'customItems' );
		customItems.unshift( new $.ui.ooMenu.CustomItem(
			this.options.messages.more,
			function() {
				var cached = self._cache[self._term];
				return cached
					&& cached.nextSuggestionOffset
					&& cached.nextSuggestionOffset > cached.suggestions.length - 1;
			},
			function() {
				self.search( $.Event( 'programmatic' ) );
			},
			'ui-entityselector-more'
		) );

		ooMenu._evaluateVisibility = function( customItem ) {
			if ( customItem instanceof $.ui.ooMenu.CustomItem ) {
				return customItem.getVisibility( ooMenu );
			} else {
				return ooMenu._evaluateVisibility.apply( this, arguments );
			}
		};

		ooMenu.option( 'customItems', customItems );

		return ooMenu;
	},

	/**
	 * @inheritdoc
	 * @protected
	 */
	_getSuggestions: function( term ) {
		var self = this;

		return $.ui.suggester.prototype._getSuggestions.apply( this, arguments )
		.then( function( suggestions, searchTerm, nextSuggestionOffset ) {
			var deferred = $.Deferred();

			if ( self._cache[searchTerm] && self._cache[searchTerm].nextSuggestionOffset ) {
				self._cache[searchTerm].suggestions = self._cache[searchTerm].suggestions.concat( suggestions );
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
	 * @inheritdoc
	 * @protected
	 */
	_getSuggestionsFromArray: function( term, source ) {
		var deferred = $.Deferred(),
			matcher = new RegExp( this._escapeRegex( term ), 'i' );

		deferred.resolve( $.grep( source, function( item ) {
			if ( item.aliases ) {
				for ( var i = 0; i < item.aliases.length; i++ ) {
					if ( matcher.test( item.aliases[i] ) ) {
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
	 * @protected
	 *
	 * @param {Object} entityStub
	 */
	_select: function( entityStub ) {
		var id = entityStub && entityStub.id;
		this._selectedEntity = entityStub;
		if ( id ) {
			this._trigger( 'selected', null, [id] );
		}
	},

	/**
	 * Gets the selected entity.
	 *
	 * @return {Object} Plain object featuring `Entity` stub data.
	 */
	selectedEntity: function() {
		return this._selectedEntity;
	}
} );

/**
 * Default `entityselector` suggestion menu item.
 *
 * @class jQuery.wikibase.entityselector.Item
 * @extends jQuery.ui.ooMenu.Item
 *
 * @constructor
 *
 * @param {jQuery|string} label
 * @param {string} value
 * @param {Object} entityStub
 *
 * @throws {Error} if a required parameter is not specified properly.
 */
var Item = function( label, value, entityStub ) {
	if ( !label || !value || !entityStub ) {
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
		 * @property {Object}
		 * @protected
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

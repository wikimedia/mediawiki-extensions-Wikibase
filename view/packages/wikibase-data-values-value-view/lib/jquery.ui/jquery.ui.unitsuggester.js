( function( $, util, mw ) {
	'use strict';

var PARENT = $.ui.suggester;

/**
 * @class jQuery.ui.languagesuggester
 * @extends jQuery.ui.suggester
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 *
 * @constructor
 */
$.widget( 'wikibase.unitsuggester', PARENT, {

	/**
	 * Options
	 * @property {Object}
	 */
	options: {
		url: 'https://www.wikidata.org/w/api.php',
		language: mw && mw.config && mw.config.get( 'wgUserLanguage' ),
		timeout: 8000
	},

	/**
	 * @property {number}
	 * @private
	 */
	_searchTimeoutHandle: null,

	/**
	 * @property {string}
	 * @private
	 */
	_selectedUrl: null,

	/**
	 * Caches retrieved results.
	 * @property {Object} [_cache={}]
	 * @private
	 */
	_cache: {},

	/**
	 * @inheritdoc
	 * @protected
	 */
	_create: function() {
		var self = this;

		this._cache = {};
		this.options.source = this._initDefaultSource();

		PARENT.prototype._create.call( this );

		this.element
			.addClass( 'ui-unitsuggester-input' )
			.prop( 'dir', $( document ).prop( 'dir' ) );

		this.options.menu.element.addClass( 'ui-unitsuggester-list' );

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
		this.element.removeClass( 'ui-unitsuggester-input' );

		this._cache = {};

		PARENT.prototype.destroy.call( this );
	},

	/**
	 * @protected
	 *
	 * @param {jQuery.Event} event
	 */
	_search: function( event ) {
		// TODO: This whole method is probably dead code now.
		var self = this;

		this._cache = {};
		this._select( null );

		clearTimeout( this._searchTimeoutHandle );
		this._searchTimeoutHandle = setTimeout( function() {
			self.search( event )
			.done( function( suggestions, requestTerm ) {
				if( !suggestions.length || self.element.val() !== requestTerm ) {
					return;
				}

				// TODO: First found item should be pre-selected, so "save" works.
				if( self._termMatchesSuggestion( requestTerm, suggestions[0] ) ) {
					console.log( 'DONE: '+requestTerm );
					self._selectFirstUnit();
//					self._trigger( 'selected', suggestions[0] );
//					self._select( suggestions[0] );
				}
			} );
		}, this.options.delay );
	},

	_selectFirstUnit: function() {
		var menu = this.options.menu,
			menuItems = menu.option( 'items' ),
			url = null;

		if( menuItems.length > 0 && menu.element.is( ':visible' ) ) {
			this.options.menu.activate( menuItems[0] );
			url = menuItems[0]._link;
		}

		if( this._selectedUrl !== url ) {
			this._selectedUrl = url;
			this._trigger(
				'selected',
				null,
				[url]
			);
		}
	},

	/**
	 * Determines whether a term matches a label.
	 * @protected
	 *
	 * @param {string} term
	 * @param {Object} suggestion
	 * @return {boolean}
	 */
	_termMatchesSuggestion: function( term, suggestion ) {
		return ( term.toLowerCase() === suggestion.id.toLowerCase() )
			|| ( suggestion.label && term.toLowerCase() === suggestion.label.toLowerCase() );
	},

	/**
	 * Create and return the data object for the api call.
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
			type: 'item'
		};
	},

	/**
	 * Initializes the default source pointing the the `wbsearchentities` API module via the URL
	 * provided in the options.
	 * @protected
	 *
	 * @return {Function}
	 */
	_initDefaultSource: function() {
		var self = this;

		return function( term ) {
			var deferred = $.Deferred(),
				data = self._getData( term );

			$.ajax( {
				url: self.options.url,
				dataType: 'jsonp',
				data: data,
				timeout: self.options.timeout
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
	 * @inheritdoc
	 * @protected
	 */
	_updateMenu: function( suggestions ) {
		var scrollTop = this.options.menu.element.scrollTop();

		PARENT.prototype._updateMenu.apply( this, arguments );

		this.options.menu.element.scrollTop( scrollTop );
	},

	/**
	 * Generates the label for a suggester entity.
	 * @protected
	 *
	 * @param {Object} entityStub
	 * @return {jQuery}
	 */
	_createLabelFromSuggestion: function( entityStub ) {
		var $suggestion = $( '<span class="ui-unitsuggester-itemcontent">' ),
			$label = $( '<span class="ui-unitsuggester-label">' ).text( entityStub.label || entityStub.id );

		if( entityStub.aliases ) {
			$label.append(
				$( '<span class="ui-unitsuggester-aliases">' ).text( ' (' + entityStub.aliases.join( ', ' ) +  ')' )
			);
		}

		$suggestion.append( $label );

		if( entityStub.description ) {
			$suggestion.append(
				$( '<span class="ui-unitsuggester-description">' )
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

		return new $.wikibase.unitsuggester.Item( $label, value, suggestion );
	},

	/**
	 * @inheritdoc
	 * @protected
	 */
	_initMenu: function( ooMenu ) {
		var self = this,
			retVal = PARENT.prototype._initMenu.apply( this, arguments );

		// TODO: Rename "retVal" to something meaningfull.
		// TODO: Check if the "off" is really needed.
		$( retVal )
		.off( 'selected.suggester' )
		.on( 'selected.unitsuggester', function( event, item ) {
			if( item.getEntityStub ) {
				// TODO: Kill the custom Item class in this file. Simply use _link instead of url.
				self._selectedUrl = item.getEntityStub().url;
				// TODO: Call the parent method instead and remove all lines below.
				self.element.val( item.getValue() );
				self._close();
				self._trigger( 'change' );
			}
		} );

		return ooMenu;
	},

	/**
	 * @inheritdoc
	 * @protected
	 */
	_getSuggestions: function( term ) {
		var self = this;

		return PARENT.prototype._getSuggestions.apply( this, arguments )
		.then( function( suggestions, searchTerm, nextSuggestionOffset ) {
			var deferred = $.Deferred();

			if( self._cache[searchTerm] ) {
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
	 * @protected
	 *
	 * @param {Object} entityStub
	 */
	_select: function( entityStub ) {
		var id = entityStub && entityStub.id;
		this._selectedUrl = entityStub && entityStub.url;
		if( id ) {
			this._trigger( 'selected', null, [id] );
		}
	},

	/**
	 * @return {string}
	 */
	getSelectedConceptUri: function() {
		return this._selectedUrl && this._selectedUrl.replace(
			/^(?:https?:)?\/\/(?:www\.)?wikidata\.org\/\w+\/(?=Q)/i,
			'http://www.wikidata.org/entity/'
		);
	}
} );

/**
 * Default `unitsuggester` suggestion menu item.
 * @class jQuery.wikibase.unitsuggester.Item
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

$.extend( $.wikibase.unitsuggester, {
	Item: Item
} );

}( jQuery, util, mediaWiki ) );

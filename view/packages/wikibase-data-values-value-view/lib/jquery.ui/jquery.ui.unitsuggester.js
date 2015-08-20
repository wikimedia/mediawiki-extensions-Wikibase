( function( $, util ) {
	'use strict';

var PARENT = $.ui.suggester;

/**
 * @class jQuery.ui.languagesuggester
 * @extends jQuery.ui.suggester
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 * @author Jonas Kress
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
		language: null,
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
			self._trigger( 'change' );
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
		var self = this;

		this._term = this.element.val();
		this._selectedUrl = null;
		this._cache = {};

		clearTimeout( this._searchTimeoutHandle );
		this._searchTimeoutHandle = setTimeout( function() {
			self.search()
			.done( function( suggestions, requestTerm ) {
				if( requestTerm !== self.element.val() ) {
					return;
				}
				if( self.options.menu.element.is( ':visible' ) ) {
					self._selectFirstUnit();
				} else {
					self._trigger( 'selected', null, [null] );
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
			this._trigger( 'selected', null, [url] );
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
		return ( term.toLowerCase() === suggestion.id.toLowerCase() ) ||
			( suggestion.label && term.toLowerCase() === suggestion.label.toLowerCase() );
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
			uselang: this.options.language,
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

		var item = new $.ui.ooMenu.Item( $label, value );
		item._link = suggestion.url;

		return item;
	},

	/**
	 * @inheritdoc
	 * @protected
	 */
	_initMenu: function( ooMenu ) {
		var self = this;

		PARENT.prototype._initMenu.apply( this, arguments );

		$( this.options.menu )
		.off( 'selected.suggester' )
		.on( 'selected.unitsuggester', function( event, item ) {
				self._term = item.getValue();
				self._selectedUrl = item._link;
				self.element.val( item.getValue() );
				self._close();
				self._trigger( 'change' );
		} );

		this.options.menu.element
		.on( 'mouseleave', function() {
			if( self.options.menu.element.is( ':visible' ) ) {
				self._selectedSite = null;
				self._selectFirstUnit();
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
	 * Returns concept URI for an item for example:
	 * http://www.wikidata.org/entity/Q650
	 * @return {string}
	 */
	getSelectedConceptUri: function() {
		return this._selectedUrl && this._selectedUrl.replace(
			/^(?:https?:)?\/\/(?:www\.)?wikidata\.org\/\w+\/(?=Q)/i,
			'http://www.wikidata.org/entity/'
		);
	}
} );

}( jQuery, util ) );

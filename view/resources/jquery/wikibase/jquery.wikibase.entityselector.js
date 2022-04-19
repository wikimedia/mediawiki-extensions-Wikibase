( function () {
	'use strict';

	// TODO: Get rid of MediaWiki context detection by submitting a message provider as option.

	/**
	 * Whether loaded in MediaWiki context.
	 *
	 * @property {boolean}
	 * @ignore
	 */
	var IS_MW_CONTEXT = mw !== undefined && mw.msg;

	/**
	 * Whether actual `entityselector` resource loader module is loaded.
	 *
	 * @property {boolean}
	 * @ignore
	 */
	var IS_MODULE_LOADED = (
		IS_MW_CONTEXT
		&& mw.loader.getModuleNames().indexOf( 'jquery.wikibase.entityselector' ) !== -1
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
		// eslint-disable-next-line mediawiki/msg-doc
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
	 * @class jQuery.wikibase.entityselector
	 * @extends jQuery.ui.suggester
	 * @uses jQuery.event.special.eachchange
	 * @uses jQuery.ui.ooMenu
	 * @license GPL-2.0-or-later
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
	 * @param {string} [options.searchHookName='wikibase.entityselector.search']
	 *        Name of the hook that fires when searching for entities.
	 * @param {string} [options.messages.more='more']
	 *        Label of the link to display more suggestions.
	 * @param {string[]} [options.showErrorCodes=['failed-property-search']]
	 *        Show errors with these error-codes in the ui.
	 * @param {Function} [options.responseErrorFactory=null]
	 *        Optional Callback to parse error message from response object
	 *        @see wikibase.api.RepoApiError.newFromApiResponse
	 */
	/**
	 * @event selected
	 * Triggered after having selected an entity.
	 * @param {jQuery.Event} event
	 * @param {string} entityId
	 */
	$.widget( 'wikibase.entityselector', $.ui.suggester, {

		/**
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
				more: mwMsgOrString( 'wikibase-entityselector-more', 'more' ),
				notfound: mwMsgOrString( 'wikibase-entityselector-notfound', 'Nothing found' ),
				error: null
			},
			searchHookName: 'wikibase.entityselector.search',
			searchApiParametersHookName: 'wikibase.entityselector.search.api-parameters',
			showErrorCodes: [ 'failed-property-search' ],
			responseErrorFactory: null
		},

		/**
		 * Caching the most current entity returned from the API.
		 *
		 * @property {Object}
		 * @private
		 */
		_selectedEntity: null,

		/**
		 * Caches retrieved results.
		 *
		 * Warning, PropertySuggester's EntitySelector accesses this!
		 *
		 * @property {Object} [_cache={}]
		 * @protected
		 */
		_cache: null,

		/**
		 * Error object from last search.
		 *
		 * @property {Object} [_error={}]
		 * @protected
		 */
		_error: null,

		/**
		 * Warning, PropertySuggester's EntitySelector overrides this!
		 *
		 * @inheritdoc
		 * @protected
		 */
		_create: function () {
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
			} else if ( typeof this.options.source !== 'function' && !Array.isArray( this.options.source ) ) {
				throw new Error( 'Source needs to be a function or an array' );
			}

			if ( !this.options.messages.error ) {
				this.options.messages.error = function () {
					return self._error && self._error.detailedMessage ? self._error.detailedMessage : null;
				};
			}

			$.ui.suggester.prototype._create.call( this );

			this.element
				.addClass( 'ui-entityselector-input' )
				.prop( 'dir', $( document ).prop( 'dir' ) );

			this.options.menu.element.addClass( 'ui-entityselector-list' );

			this.element
			.off( 'blur' )
			.on( 'eachchange.' + this.widgetName, function ( event ) {
				self._search( event );
			} )
			.on( 'focusout', function () {
				self._indicateRecognizedInput();
			} )
			.on( 'focusin', function () {
				self._inEditMode();
				self._showDefaultSuggestions();
			} );
		},

		_indicateRecognizedInput: function () {
			this._resetInputHighlighting();

			if ( this._selectedEntity !== null ) {
				this.element.addClass( 'ui-entityselector-input-recognized' );
			} else if ( this.element.val() !== '' ) {
				this.element.addClass( 'ui-entityselector-input-unrecognized' );
			}
		},

		_inEditMode: function () {
			this._resetInputHighlighting();
		},

		_resetInputHighlighting: function () {
			this.element.removeClass(
				'ui-entityselector-input-recognized ui-entityselector-input-unrecognized'
			);
		},

		/**
		 * @inheritdoc
		 */
		destroy: function () {
			this.element.removeClass( 'ui-entityselector-input' );

			this._cache = {};

			$.ui.suggester.prototype.destroy.call( this );
		},

		/**
		 * @protected
		 *
		 * @param {jQuery.Event} event
		 */
		_search: function ( event ) {
			var self = this;

			this._select( null );

			clearTimeout( this._searching );
			this._searching = setTimeout( function () {
				self.search( event );
			}, this.options.delay );
		},

		/**
		 * Create and return the data object for the api call.
		 *
		 * Warning, PropertySuggester's EntitySelector overrides this!
		 *
		 * @protected
		 * @param {string} term
		 * @return {Object}
		 */
		_getSearchApiParameters: function ( term ) {
			var data = {
				action: 'wbsearchentities',
				search: term,
				format: 'json',
				errorformat: 'plaintext',
				language: this.options.language,
				uselang: this.options.language,
				type: this.options.type
			};

			if ( this._cache.term === term && this._cache.nextSuggestionOffset ) {
				data.continue = this._cache.nextSuggestionOffset;
			}

			if ( this.options.limit ) {
				data.limit = this.options.limit;
			}

			mw.hook( this.options.searchApiParametersHookName ).fire( data );

			return data;
		},

		/**
		 * Initializes the default source pointing to the `wbsearchentities` API module via the URL
		 * provided in the options.
		 *
		 * @protected
		 *
		 * @return {Function}
		 */
		_initDefaultSource: function () {
			var self = this;

			return function ( term ) {
				var deferred = $.Deferred(),
					hookResults = self._fireSearchHook( term );

				// clear previous error
				if ( self._error ) {
					self._error = null;
					self._cache.suggestions = null;
					self._updateMenu( [] );
				}
				$.ajax( {
					url: self.options.url,
					timeout: self.options.timeout,
					dataType: 'json',
					data: self._getSearchApiParameters( term )
				} )
				.done( function ( response, statusText, jqXHR ) {
					// T141955
					if ( response.error ) {
						deferred.reject( response.error.info );
						return;
					}

					// The default endpoint wbsearchentities responds with an array of errors.
					if ( response.errors && self.options.responseErrorFactory ) {
						var error = self.options.responseErrorFactory( response, 'search' );

						if ( error && self.options.showErrorCodes.indexOf( error.code ) !== -1 ) {
							self._error = error;
							self._cache = {};
							self._updateMenu( [] );
							deferred.reject( error.message );
							return;
						}
					}
					self._combineResults( hookResults, response.search ).then( function ( results ) {
						deferred.resolve(
							results,
							term,
							response[ 'search-continue' ],
							jqXHR.getResponseHeader( 'X-Search-ID' )
						);
					} );
				} )
				.fail( function ( jqXHR, textStatus ) {
					deferred.reject( textStatus );
				} );

				return deferred.promise();
			};
		},
		/**
		 * @private
		 */
		_fireSearchHook: function ( term ) {
			var hookResults = [],
				addPromise = function ( p ) {
					hookResults.push( p );
				};

			if ( this._cache.term === term ) {
				return hookResults; // Don't fire hook when paginating
			}

			mw.hook( this.options.searchHookName ).fire( {
				element: this.element,
				term: term,
				options: this.options
			}, addPromise );

			return hookResults;
		},
		/**
		 * @private
		 */
		_combineResults: function ( hookResults, searchResults ) {
			var self = this,
				deferred = $.Deferred(),
				ids = {},
				result = [],
				uniqueFilter = function ( item ) {
					if ( ids[ item.id ] ) {
						return false;
					}
					ids[ item.id ] = true;
					return true;
				},
				ratingSorter = function ( item1, item2 ) {
					if ( !item1.rating && !item2.rating ) {
						return 0;
					}
					if ( !item1.rating ) {
						return 1;
					}
					if ( !item2.rating ) {
						return -1;
					}
					if ( item1.rating < item2.rating ) {
						return 1;
					}
					if ( item1.rating === item2.rating ) {
						return 0;
					}
					return -1;
				};

			searchResults = searchResults || [];

			$.when.apply( $, hookResults ).then( function () {

				var args = Array.prototype.slice.call( arguments );
				args.forEach( function ( data ) {
					result = data.concat( result );
				} );

				result = self._stableSort( result, ratingSorter );
				result = result.concat( searchResults );
				result = result.filter( uniqueFilter );
				deferred.resolve( result );
			} );

			return deferred.promise();
		},

		/**
		 * @private
		 */
		_stableSort: function stableSort( items, compareFn ) {
			var indices = Object.keys( items ).map( Number );
			indices.sort( function ( index1, index2 ) {
				var compare = compareFn( items[ index1 ], items[ index2 ] );
				if ( compare !== 0 ) {
					return compare;
				}
				// fall back to comparing indices to ensure stability
				if ( index1 < index2 ) {
					return -1;
				}
				if ( index1 > index2 ) {
					return 1;
				}
				return 0;
			} );
			var sorted = indices.map( function ( index ) {
				return items[ index ];
			} );
			return sorted;
		},

		/**
		 * @private
		 */
		_showDefaultSuggestions: function () {
			if ( this.element.val() !== '' ) {
				return;
			}

			var self = this,
				term = this.element.val(),
				promises = this._fireSearchHook( term );

			this._combineResults( promises, [] ).then( function ( suggestions ) {
				if ( suggestions.length > 0 ) {
					self._updateMenu( suggestions );
				}
			} );

		},

		/**
		 * When the input is focused,
		 * don’t open suggestions again if an entity was already selected.
		 *
		 * @protected
		 *
		 * @return {boolean}
		 */
		_shouldSearch: function () {
			return this._selectedEntity === null;
		},

		/**
		 * @inheritdoc
		 * @protected
		 */
		_updateMenu: function ( suggestions ) {
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
		_createLabelFromSuggestion: function ( entityStub ) {
			var $suggestion = $( '<span>' ).addClass( 'ui-entityselector-itemcontent' ),
				$label = $( '<span>' ).addClass( 'ui-entityselector-label' ),
				$description = $();

			if ( entityStub.display && entityStub.display.label ) {
				$label.text( entityStub.display.label.value );
				$label.attr( 'lang', entityStub.display.label.language );
			} else {
				$label.text( entityStub.label || entityStub.id );
			}

			// TODO use match instead of aliases
			if ( entityStub.aliases ) {
				$label.append(
					$( '<span>' ).addClass( 'ui-entityselector-aliases' ).text( ' (' + entityStub.aliases.join( ', ' ) + ')' )
				);
			}

			$suggestion.append( $label );

			if ( entityStub.display && entityStub.display.description ) {
				$description = $( '<span>' ).addClass( 'ui-entityselector-description' )
					.text( entityStub.display.description.value )
					.attr( 'lang', entityStub.display.description.language );
			} else if ( entityStub.description ) {
				$description = $( '<span>' ).addClass( 'ui-entityselector-description' )
					.text( entityStub.description );
			}

			$suggestion.append( $description );

			return $suggestion;
		},

		/**
		 * @see jQuery.ui.suggester._createMenuItemFromSuggestion
		 * @protected
		 *
		 * @param {Object} entityStub
		 * @return {jQuery.wikibase.entityselector.Item}
		 */
		_createMenuItemFromSuggestion: function ( entityStub ) {
			var $label = this._createLabelFromSuggestion( entityStub ),
				value;

			if ( entityStub.display && entityStub.display.label ) {
				value = entityStub.display.label.value;
			} else {
				value = entityStub.label || entityStub.id;
			}

			return new $.wikibase.entityselector.Item( $label, value, entityStub );
		},

		/**
		 * @inheritdoc
		 * @protected
		 */
		_initMenu: function ( ooMenu ) {
			var self = this;
			$.ui.suggester.prototype._initMenu.apply( this, arguments );

			$( this.options.menu )
			.off( 'selected.suggester' )
			.on( 'selected.entityselector', function ( event, item ) {
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
				function () {
					return self._cache.term === self._term && self._cache.nextSuggestionOffset;
				},
				function () {
					self.search( $.Event( 'programmatic' ) );
				},
				'ui-entityselector-more'
			) );

			customItems.unshift( new $.ui.ooMenu.CustomItem(
				this.options.messages.notfound,
				function () {
					return !self._error && self._cache.suggestions && !self._cache.suggestions.length
						&& self.element.val().trim() !== '';
				},
				null,
				'ui-entityselector-notfound'
			) );

			customItems.unshift( new $.ui.ooMenu.CustomItem(
				this.options.messages.error,
				function () {
					return self._error !== null;
				},
				null,
				'ui-entityselector-error'
			) );

			ooMenu._evaluateVisibility = function ( customItem ) {
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
		_getSuggestions: function ( term ) {
			var self = this;

			return $.ui.suggester.prototype._getSuggestions.apply( this, arguments )
			.then( function ( suggestions, searchTerm, nextSuggestionOffset, searchId ) {
				var deferred = $.Deferred();

				if ( self._cache.term === searchTerm && self._cache.nextSuggestionOffset ) {
					self._cache.suggestions = self._cache.suggestions.concat( suggestions );
					self._cache.nextSuggestionOffset = nextSuggestionOffset;
				} else {
					self._cache = {
						term: searchTerm,
						suggestions: suggestions,
						nextSuggestionOffset: nextSuggestionOffset
					};
				}
				if ( searchId ) {
					self._cache.searchId = searchId;
				} else {
					delete self._cache.searchId;
				}

				deferred.resolve( self._cache.suggestions, searchTerm );
				return deferred.promise();
			} );
		},

		/**
		 * @inheritdoc
		 * @protected
		 */
		_getSuggestionsFromArray: function ( term, source ) {
			var deferred = $.Deferred(),
				matcher = new RegExp( this._escapeRegex( term ), 'i' );

			deferred.resolve( source.filter( function ( item ) {
				// TODO use match instead of aliases
				if ( item.aliases ) {
					for ( var i = 0; i < item.aliases.length; i++ ) {
						if ( matcher.test( item.aliases[ i ] ) ) {
							return true;
						}
					}
				}

				var label;
				if ( item.display && item.display.label ) {
					label = item.display.label.value;
				} else {
					label = item.label || '';
				}

				return matcher.test( label ) || matcher.test( item.id );
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
		_select: function ( entityStub ) {
			var id = entityStub && entityStub.id;
			this._selectedEntity = entityStub;
			if ( id ) {
				this._trigger( 'selected', null, [ id ] );
			}
		},

		/**
		 * Gets and sets the current state. The optional parameter can be used to let the initial
		 * state of the selector reflect what can be seen in the input field the selector is
		 * attached to.
		 *
		 * @param {string} [entityId]
		 * @return {Object} Plain object featuring `Entity` stub data.
		 */
		selectedEntity: function ( entityId ) {
			if ( typeof entityId === 'string' ) {
				this._selectedEntity = { id: entityId };
			}

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
	var Item = function ( label, value, entityStub ) {
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
			getEntityStub: function () {
				return this._entityStub;
			}
		}
	);

	$.extend( $.wikibase.entityselector, {
		Item: Item
	} );

}() );

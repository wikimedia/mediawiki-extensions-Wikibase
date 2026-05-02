( function () {
	'use strict';

	var NAMESPACE = {
		ALL: '*',
		File: 6,
		Data: 486
	};

	/**
	 * Commons suggester.
	 * Enhances an input box with suggestion functionality for Wikimedia Commons asset names.
	 * (uses `util.highlightSubstring`)
	 *
	 * @class jQuery.ui.commonssuggester
	 * @extends jQuery.ui.suggester
	 * @uses util
	 * @license GNU GPL v2+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 */
	$.widget( 'ui.commonssuggester', $.ui.suggester, {

		/**
		 * @see jQuery.ui.suggester.options
		 */
		options: {
			ajax: $.ajax,
			apiUrl: null,
			namespace: null,
			contentModel: null
		},

		/**
		 * @inheritdoc
		 * @protected
		 */
		_create: function() {
			if ( !this.options.apiUrl ) {
				throw new Error( 'apiUrl option required' );
			}

			if ( !this.options.source ) {
				this.options.source = this._initDefaultSource();
			}

			$.ui.suggester.prototype._create.call( this );

			this.options.menu.element.addClass( 'ui-commonssuggester-list' );
		},

		/**
		 * Initializes the default source pointing to the "query" API module on Wikimedia Commons.
		 *
		 * @protected
		 *
		 * @return {Function}
		 */
		_initDefaultSource: function() {
			var self = this;

			return function( term ) {
				var deferred = $.Deferred();

				self.options.ajax( {
					url: self.options.apiUrl,
					dataType: 'jsonp',
					data: {
						action: 'query',
						list: 'search',
						srsearch: self._getSearchString( term ),
						srnamespace: NAMESPACE[self.options.namespace] || NAMESPACE.ALL,
						srlimit: 10,
						format: 'json'
					},
					timeout: 8000
				} )
				.done( function( response ) {
					var sorted = self._prioritiseMatchingFilename( response.query.search, term );

					deferred.resolve( sorted, term );
				} )
				.fail( function( jqXHR, textStatus ) {
					// Since this is a JSONP request, this will always fail with a timeout...
					deferred.reject( textStatus );
				} );

				return deferred.promise();
			};
		},

		/**
		 * Be smart on the commons search results and put an exactly matching file name on top
		 *
		 * @private
		 *
		 * @param {Array} resultList Results from the search API response
		 * @param {string} term The user's search term
		 *
		 * @return {Array}
		 */
		_prioritiseMatchingFilename: function( resultList, term ) {
			return resultList.sort( function( a, b ) {
				// use indexOf() in favour of startsWith() for browser compatibility
				if ( a.title.indexOf( 'File:' + term ) === 0 ) {
					return -1;
				} else if ( b.title.indexOf( 'File:' + term ) === 0 ) {
					return 1;
				} else {
					return 0;
				}
			} );
		},

		/**
		 * @private
		 *
		 * @param {string} term
		 * @return {string}
		 */
		_getSearchString: function( term ) {
			var searchString = this._grepFileTitleFromTerm( term );

			if ( this.options.contentModel ) {
				searchString += ' contentmodel:' + this.options.contentModel;
			}

			return searchString;
		},

		/**
		 * @private
		 *
		 * @param {string} term
		 * @return {string}
		 */
		_grepFileTitleFromTerm: function( term ) {
			try {
				// Make sure there are always at least 2 characters left
				return decodeURIComponent( term
					.replace( /^[^#]*\btitle=([^&#]{2,}).*/, '$1' )
					.replace( /^[^#]*\/([^/?#]{2,}).*/, '$1' )
				);
			} catch ( ex ) {
				// Revert all replacements when the input was not a (fragment of a) valid URL
				return term;
			}
		},

		/**
		 * @see jQuery.ui.suggester._createMenuItemFromSuggestion
		 * @protected
		 *
		 * @param {Object} suggestion
		 * @param {string} requestTerm
		 * @return {jQuery.ui.ooMenu.Item}
		 */
		_createMenuItemFromSuggestion: function( suggestion, requestTerm ) {
			suggestion = suggestion.title;

			var isFile = /^File:/.test( suggestion );

			if ( isFile ) {
				suggestion = suggestion.replace( /^File:/, '' );
			}

			var label = util.highlightSubstring(
					requestTerm,
					suggestion,
					{
						caseSensitive: false,
						withinString: true
					}
				),
				$label = $( '<span>' )
					.attr( { dir: 'ltr', title: suggestion } )
					.append( label );

			if ( isFile ) {
				$label.prepend( this._createThumbnail( suggestion ) );
			}

			return new $.ui.ooMenu.Item( $label, suggestion );
		},

		/**
		 * @private
		 *
		 * @param {string} fileName Must be a file name without the File: namespace.
		 * @return {jQuery}
		 */
		_createThumbnail: function( fileName ) {
			return $( '<span>' )
				.attr( 'class', 'ui-commonssuggester-thumbnail' )
				.css( 'background-image', this._createBackgroundImage( fileName ) );
		},

		/**
		 * @private
		 *
		 * @param {string} fileName Must be a file name without the File: namespace.
		 * @return {string} CSS
		 */
		_createBackgroundImage: function ( fileName ) {
			// Height alone is ignored, width must be set to something.
			// We accept to truncate 50% and only show the center 50% of the images area.
			return 'url("https://commons.wikimedia.org/wiki/Special:Filepath/'
				+ encodeURIComponent( fileName )
				+ '?width=100&height=50")';
		}

	} );

}() );

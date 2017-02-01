( function( $, util ) {
	'use strict';

	// TODO: Avoid using namespaces here instead use filters within opensearch
	var NAMESPACE = {
		File: 6,
		Data: 486
	};

	/**
	 * Commons suggester.
	 * Enhances an input box with suggestion functionality for Wikimedia Commons asset names.
	 * (uses `util.highlightSubstring`)
	 * @class jQuery.ui.commonssuggester
	 * @extends jQuery.ui.suggester
	 * @uses util
	 * @licence GNU GPL v2+
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
			namespace: null
		},

		/**
		 * @inheritdoc
		 * @protected
		 */
		_create: function() {
			if ( !this.options.source ) {
				this.options.source = this._initDefaultSource();
			}
			$.ui.suggester.prototype._create.call( this );
		},

		/**
		 * Initializes the default source pointing the "opensearch" API module on Wikimedia Commons.
		 * @protected
		 *
		 * @return {Function}
		 */
		_initDefaultSource: function() {
			var self = this;

			return function( term ) {
				var deferred = $.Deferred();

				self.options.ajax( {
					url: 'https://commons.wikimedia.org/w/api.php',
					dataType: 'jsonp',
					data: {
						search: self._grepFileTitleFromTerm( term ),
						action: 'opensearch',
						namespace: NAMESPACE[self.options.namespace] || NAMESPACE.File
					},
					timeout: 8000
				} )
				.done( function( response ) {
					deferred.resolve( response[1], term );
				} )
				.fail( function( jqXHR, textStatus ) {
					// Since this is a JSONP request, this will always fail with a timeout...
					deferred.reject( textStatus );
				} );

				return deferred.promise();
			};
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
		 * @param {string} suggestion
		 * @param {string} requestTerm
		 * @return {jQuery.ui.ooMenu.Item}
		 */
		_createMenuItemFromSuggestion: function( suggestion, requestTerm ) {
			suggestion = suggestion.replace( /^File:/, '' );

			var label = suggestion;

			if ( requestTerm ) {
				label = util.highlightSubstring( requestTerm, suggestion );
			}

			return new $.ui.ooMenu.Item( label, suggestion );
		}

	} );

}( jQuery, util ) );

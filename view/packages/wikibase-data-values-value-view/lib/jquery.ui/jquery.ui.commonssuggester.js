( function( $, util ) {
	'use strict';

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
		 * @inheritdoc
		 * @protected
		 */
		_create: function() {
			if( !this.options.source ) {
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
			return function( term ) {
				var deferred = $.Deferred();

				$.ajax( {
					url: 'https://commons.wikimedia.org/w/api.php',
					dataType: 'jsonp',
					data: {
						search: term,
						action: 'opensearch',
						namespace: 6
					},
					timeout: 8000
				} )
				.done( function( response ) {
					deferred.resolve( response[1], response[0] );
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
		_createMenuItemFromSuggestion: function( suggestion, requestTerm ) {
			suggestion = suggestion.replace( /^File:/, '' );

			var label = suggestion;

			if( requestTerm ) {
				label = util.highlightSubstring( requestTerm, suggestion );
			}

			return new $.ui.ooMenu.Item( label, suggestion );
		}

	} );

}( jQuery, util ) );

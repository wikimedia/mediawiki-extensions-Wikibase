/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, $ ) {
	'use strict';

/**
 * Suggester enhanced input element for selecting a site link's page.
 * @since 0.5
 *
 * @option {wikibase.Site} [site]
 *
 * @option {string} [pageTitle]
 */
$.widget( 'wikibase.pagesuggester', $.ui.suggester, {
	/**
	 * @see jQuery.ui.suggester.options
	 */
	options: {
		site: null,
		pageTitle: null
	},

	/**
	 * @see jQuery.ui.suggester._create
	 *
	 * @throws {Error} if no SiteLink object is passed via options.
	 */
	_create: function() {
		var self = this;

		if( this.option( 'pageTitle' ) ) {
			this.element.val( this.option( 'pageTitle' ) );
		}

		if( !this.option( 'source' ) ) {
			this.option( 'source', this._request() );
		}

		$.ui.suggester.prototype._create.call( this );

		this.element
		.on( this.widgetEventPrefix + 'change.' + this.widgetName, function( event ) {
			if( $.trim( self.element.val() ) !== self.option( 'pageTitle' ) ) {
				self.option( 'pageTitle', $.trim( self.element.val() ) );
			}
		} );
	},

	/**
	 * @see jQuery.ui.suggester._setOption
	 */
	_setOption: function( key, value ) {
		$.ui.suggester.prototype._setOption.apply( this, arguments );

		if( key === 'site' ) {
			this._trigger( 'change' );
		}

		if( key === 'pageTitle' ) {
			this.element.val( this.option( 'pageTitle' ) );
			this._trigger( 'change' );
		}
	},

	/**
	 * @see $.ui.suggester.search
	 */
	search: function( event ) {
		// Reject searching when there is no site specified:
		if( !this.option( 'site' ) ) {
			var deferred = $.Deferred();
			return deferred.reject( 'site-undefined' ).promise();
		}
		return $.ui.suggester.prototype.search.apply( this, arguments );
	},

	/**
	 * @see jQuery.ui.suggester._getSuggestions
	 *
	 * @return {Object} jQuery.Promise
	 *         Resolved parameters:
	 *         - {string[]}
	 *         - {string}
	 *         Rejected parameters:
	 *         - {string}
	 */
	_request: function() {
		var self = this;

		return function( term ) {
			var deferred = $.Deferred();

			$.ajax( {
				url: self.option( 'site' ).getApi(),
				dataType: 'jsonp',
				data: {
					search: term,
					action: 'opensearch'
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
	}

} );
}( mediaWiki, jQuery ) );

/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, wb ) {
	'use strict';

/**
 * Suggester enhanced input element for selecting a site link's page.
 * @since 0.5
 *
 * @option {string} [siteId]
 *
 * @option {string} [pageName]
 */
$.widget( 'wikibase.pagesuggester', $.ui.suggester, {
	/**
	 * @see jQuery.ui.suggester.options
	 */
	options: {
		siteId: null,
		pageName: null
	},

	/**
	 * @see jQuery.ui.suggester._create
	 */
	_create: function() {
		var self = this;

		if( this.option( 'pageName' ) ) {
			this.element.val( this.option( 'pageName' ) );
		}

		if( !this.option( 'source' ) ) {
			this.option( 'source', this._request() );
		}

		$.ui.suggester.prototype._create.call( this );

		this.element
		.on( this.widgetEventPrefix + 'change.' + this.widgetName, function( event ) {
			var value = $.trim( self.element.val() );
			if( value !== self.option( 'pageName' ) ) {
				self.option( 'pageName', value );
			}
		} );
	},

	/**
	 * @see jQuery.ui.suggester._setOption
	 */
	_setOption: function( key, value ) {
		$.ui.suggester.prototype._setOption.apply( this, arguments );

		if( key === 'siteId' ) {
			this._trigger( 'change' );
		}

		if( key === 'pageName' ) {
			this.element.val( value );
			this._trigger( 'change' );
		}
	},

	/**
	 * @see $.ui.suggester.search
	 */
	search: function( event ) {
		// Reject searching when there is no siteId specified:
		if( !this.option( 'siteId' ) ) {
			var deferred = $.Deferred();
			return deferred.reject( 'siteId-undefined' ).promise();
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
				url: wb.sites.getSite( self.option( 'siteId' ) ).getApi(),
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
}( jQuery, wikibase ) );

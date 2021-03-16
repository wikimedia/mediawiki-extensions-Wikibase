( function ( wb ) {
	'use strict';

	/**
	 * @ignore
	 *
	 * @param {string} url
	 * @return {string}
	 */
	function getHost( url ) {
		// Internet Explorer returns an incomplete host (without port) when the protocol is missing.
		if ( /^\/\//.test( url ) ) {
			url = location.protocol + url;
		}

		var parser = document.createElement( 'A' );
		parser.href = url;
		return parser.host;
	}

	// TODO: Merge this into mw.Api
	/**
	 * Returns a `mediaWiki.Api` instance which can transparently interact with remote APIs.
	 * @member wikibase.api
	 * @method getLocationAgnosticMwApi
	 * @since 1.0
	 * @license GPL-2.0+
	 * @author Adrian Lang < adrian.lang@wikimedia.de >
	 *
	 * @param {string} apiEndpoint
	 * @param {Object} [options]
	 * @return {mediaWiki.Api}
	 */
	wb.api.getLocationAgnosticMwApi = function ( apiEndpoint, options ) {
		if ( getHost( apiEndpoint ) !== getHost( location.href ) ) {
			// Use mw.ForeignApi if the api we want to use is on a different domain.
			return new mw.ForeignApi( apiEndpoint, options );
		}

		var mwApiOptions = $.extend( {}, options, {
			ajax: {
				url: apiEndpoint
			}
		} );

		return new mw.Api( mwApiOptions );
	};

}( wikibase ) );

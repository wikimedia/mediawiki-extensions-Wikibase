( function( mw, wb ) {
'use strict';

/**
 * @ignore
 *
 * @param {string} url
 * @return {string}
 */
function getHost( url ) {
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
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 *
 * @param {string} apiEndpoint
 * @return {mediaWiki.Api}
 */
wb.api.getLocationAgnosticMwApi = function( apiEndpoint ) {
	var localApiEndpoint = mw.config.get( 'wgServer' )
		+ mw.config.get( 'wgScriptPath' )
		+ '/api.php';

	var mwApiOptions = {
		ajax: {
			url: apiEndpoint
		}
	};

	if ( getHost( localApiEndpoint ) !== getHost( apiEndpoint ) ) {
		// Use CORS if the api we want to use is on a different domain.
		// But don't if it's not: CORS isn't required if we are on the same domain, thus it
		// might not be configured and fail.
		var corsOrigin = mw.config.get( 'wgServer' );
		if ( corsOrigin.indexOf( '//' ) === 0 ) {
			// The origin parameter musn't be protocol relative
			corsOrigin = document.location.protocol + corsOrigin;
		}

		mwApiOptions.ajax.xhrFields = {
			withCredentials: true
		};
		mwApiOptions.ajax.crossDomain = true;
		mwApiOptions.parameters = {
			origin: corsOrigin
		};
	}

	return new mw.Api( mwApiOptions );
};

}( mediaWiki, wikibase ) );

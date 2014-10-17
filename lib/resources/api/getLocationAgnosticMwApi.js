/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
( function( mw, wb ) {
'use strict';

/**
 * @param {string} url
 * @return {string}
 */
function getDomainName( url ) {
	return url.replace( /.*\/\//, '' ).replace( /\/.*/, '' );
}

/**
 * Returns a mediaWiki.Api instance which can transparently interact with remote APIs.
 * @since 0.5
 * @todo Merge this into mw.Api
 *
 * @param {string} apiEndpoint
 * @return {mediaWiki.Api}
 */
wb.api.getLocationAgnosticMwApi = function( apiEndpoint ) {
	var localApiEndpoint = mw.config.get( 'wgServer' )
		+ mw.config.get( 'wgScriptPath' )
		+ '/api.php';
	var mwApiOptions = {};

	if( localApiEndpoint !== apiEndpoint ) {
		var sameDomain = getDomainName( localApiEndpoint ) === getDomainName( apiEndpoint );

		mwApiOptions = {
			ajax: {
				url: apiEndpoint
			}
		};

		if ( !sameDomain ) {
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

	}

	return new mw.Api( mwApiOptions );
};

}( mediaWiki, wikibase ) );

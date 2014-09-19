/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
( function( mw, wb ) {
'use strict';

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
		var corsOrigin = mw.config.get( 'wgServer' );
		if ( corsOrigin.indexOf( '//' ) === 0 ) {
			// The origin parameter musn't be protocol relative
			corsOrigin = document.location.protocol + corsOrigin;
		}
		mwApiOptions = {
			ajax: {
				url: apiEndpoint,
				xhrFields: {
					withCredentials: true
				},
				crossDomain: true
			},
			parameters: {
				origin: corsOrigin
			}
		};
	}

	return new mw.Api( mwApiOptions );
};

}( mediaWiki, wikibase ) );

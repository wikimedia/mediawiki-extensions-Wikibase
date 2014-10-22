/*
 * @licence GNU GPL v2+
 * @author: Adrian Lang <adrian.lang@wikimedia.de>
 */
( function( mw, wb ) {
	'use strict';

	var MODULE = wb.client;

	/**
	 * @return {mediaWiki.Api}
	 */
	MODULE.getMwApiForRepo = function() {
		var repoConfig = mw.config.get( 'wbRepo' ),
			repoApiEndpoint = repoConfig.url + repoConfig.scriptPath + '/api.php';

		return wikibase.api.getLocationAgnosticMwApi( repoApiEndpoint );
	};
}( mediaWiki, wikibase ) );

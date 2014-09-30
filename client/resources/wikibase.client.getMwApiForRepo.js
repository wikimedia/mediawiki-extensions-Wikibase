/*
 * @licence GNU GPL v2+
 * @author: Adrian Lang <adrian.lang@wikimedia.de>
 */
( function( mw, wb ) {
	'use strict';

	var MODULE = wb;

	MODULE.getMwApiForRepo = function() {
		var repoConfig = mw.config.get( 'wbRepo' ),
			repoApiEndpoint = repoConfig.url + repoConfig.scriptPath + '/api.php',
			mwApiForRepo = wikibase.api.getLocationAgnosticMwApi( repoApiEndpoint );
		return mwApiForRepo;
	};
}( mediawiki, wikibase ) );

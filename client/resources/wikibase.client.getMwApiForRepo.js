/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( mw, wb ) {
	'use strict';

	var MODULE = wb.client = wb.client || {};

	/**
	 * @return {mediaWiki.Api}
	 */
	MODULE.getMwApiForRepo = function () {
		var repoConfig = mw.config.get( 'wbRepo' ),
			repoApiEndpoint = repoConfig.url + repoConfig.scriptPath + '/api.php';

		return wikibase.api.getLocationAgnosticMwApi( repoApiEndpoint );
	};
}( mediaWiki, wikibase ) );

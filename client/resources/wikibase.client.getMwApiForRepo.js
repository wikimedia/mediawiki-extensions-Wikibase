/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function () {
	'use strict';

	/**
	 * @return {mw.Api}
	 */
	var getMwApiForRepo = function () {
		var repoConfig = mw.config.get( 'wbRepo' ),
			repoApiEndpoint = repoConfig.url + repoConfig.scriptPath + '/api.php';

		return wikibase.api.getLocationAgnosticMwApi( repoApiEndpoint );
	};

	module.exports = getMwApiForRepo;
}() );

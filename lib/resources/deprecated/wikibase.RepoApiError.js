/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */

'use strict';

mediaWiki.log.deprecate(
	wikibase,
	'RepoApiError',
	wikibase.api.RepoApiError,
	'It has been moved from wikibase.RepoApiError to wikibase.api.RepoApiError.'
);

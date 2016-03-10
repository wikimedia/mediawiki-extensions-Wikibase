/**
 * @license GPL-2.0+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */

( function ( wb, mw ) {
'use strict';

mw.log.deprecate(
	wb,
	'RepoApiError',
	wb.api.RepoApiError,
	'It has been moved from wikibase.RepoApiError to wikibase.api.RepoApiError.'
);
} )( wikibase, mediaWiki );

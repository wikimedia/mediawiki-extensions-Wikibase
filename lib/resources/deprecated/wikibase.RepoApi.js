/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */

( function ( wb, mw ) {
'use strict';

mw.log.deprecate(
	wb,
	'RepoApi',
	wb.api.RepoApi,
	'It has been moved from wikibase.RepoApi to wikibase.api.RepoApi.'
);
} )( wikibase, mediaWiki );

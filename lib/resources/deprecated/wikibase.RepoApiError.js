/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */

( function ( wb, mw ) {
	'use strict';

	mw.log.deprecate(
		wb,
		'RepoApiError',
		wb.api.RepoApiError,
		'It has been moved from wikibase.RepoApiError to wikibase.api.RepoApiError.'
	);
}( wikibase, mediaWiki ) );

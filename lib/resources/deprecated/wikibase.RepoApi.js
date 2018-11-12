/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */

( function ( wb ) {
	'use strict';

	mw.log.deprecate(
		wb,
		'RepoApi',
		wb.api.RepoApi,
		'It has been moved from wikibase.RepoApi to wikibase.api.RepoApi.'
	);
}( wikibase ) );

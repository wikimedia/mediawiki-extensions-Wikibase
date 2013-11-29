/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, vv ) {
	'use strict';

	// Register Wikibase specific parsers:

	mw.ext.valueParsers.valueParserProvider.registerParser(
		wb.EntityId,
		wb.EntityIdParser
	);

}( mediaWiki, wikibase, jQuery.valueview ) );

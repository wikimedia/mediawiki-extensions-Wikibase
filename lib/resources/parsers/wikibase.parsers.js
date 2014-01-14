/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb ) {
	'use strict';

	// Register Wikibase specific parsers:

	mw.ext.valueParsers.valueParserProvider.registerParser(
		wb.EntityIdParser,
		wb.EntityId.TYPE
	);

}( mediaWiki, wikibase ) );

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

	/**
	 * Update valueView's default valueParserProvider option.
	 * @see mw.ext.valueParsers
	 */
//	vv.prototype.options.valueParserProvider = mw.ext.valueParserProvider;

}( mediaWiki, wikibase, jQuery.valueview ) );

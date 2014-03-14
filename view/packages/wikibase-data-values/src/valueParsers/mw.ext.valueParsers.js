/**
 * Entrypoint for MediaWiki "ValueParsers" extension JavaScript code.
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, dv, vp ) {
	'use strict';

	mw.ext = mw.ext || {};

	var valueParserProvider = new vp.ValueParserFactory( vp.NullParser );

	valueParserProvider.registerDataValueParser(
		vp.StringParser,
		dv.StringValue.TYPE
	);

	/**
	 * Object representing the MediaWiki "ValueParsers" extension.
	 * @since 0.1
	 */
	mw.ext.valueParsers = new ( function MwExtValueParsers() {
		/**
		 * Value parser provider containing all value parsers available in global MediaWiki context.
		 * @since 0.1
		 *
		 * @type {valueParsers.ValueParserFactory}
		 */
		this.valueParserProvider = valueParserProvider;
	} )();

}( mediaWiki, dataValues, valueParsers ) );

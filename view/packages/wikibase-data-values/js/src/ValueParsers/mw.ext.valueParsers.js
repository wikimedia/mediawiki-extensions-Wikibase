/**
 * Entrypoint for MediaWiki "ValueParsers" extension JavaScript code.
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, dv, vp, vv ) {
	'use strict';

	mw.ext = mw.ext || {};

	var valueParserProvider = new vp.ValueParserFactory( vp.NullParser );

	valueParserProvider.registerParser(
		dv.GlobeCoordinateValue.TYPE,
		vp.GlobeCoordinateParser
	);

	valueParserProvider.registerParser(
		dv.QuantityValue.TYPE,
		vp.QuantityParser
	);

	valueParserProvider.registerParser(
		dv.StringValue.TYPE,
		vp.StringParser
	);

	valueParserProvider.registerParser(
		dv.TimeValue.TYPE,
		vp.TimeParser
	);

	/**
	 * Object representing the MediaWiki "ValueParsers" extension.
	 * @since 0.1
	 */
	mw.ext.valueParsers = new ( function MwExtValueView() {
		/**
		 * Value parser provider holding all value parsers available in global MediaWiki context.
		 * @since 0.1
		 *
		 * @type {valueParsers.ValueParserFactory}
		 */
		this.valueParserProvider = valueParserProvider;
	} )();

	// 'valueParserProvider' is a required option in the original jQuery.valueview widget
	// implementation. However, if valueParsers is used in MediaWiki context, then the option should
	// not be required anymore and default to the ValueParserFactory object set in
	// mw.ext.valueParsers.
	vv.prototype.options.valueParserProvider = valueParserProvider;

}( mediaWiki, dataValues, valueParsers, jQuery.valueview ) );

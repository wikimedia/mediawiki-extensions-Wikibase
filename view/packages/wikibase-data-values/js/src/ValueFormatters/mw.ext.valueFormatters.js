/**
 * Entrypoint for MediaWiki "ValueFormatters" extension JavaScript code.
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, dv, vf, vv ) {
	'use strict';

	mw.ext = mw.ext || {};

	var valueFormatterProvider = new vf.ValueFormatterFactory( vf.NullFormatter );

	valueFormatterProvider.registerFormatter(
		dv.StringValue.TYPE,
		vf.StringFormatter
	);

	/**
	 * Object representing the MediaWiki "ValueFormatters" extension.
	 * @since 0.1
	 */
	mw.ext.valueFormatters = new ( function MwExtValueFormatters() {
		/**
		 * Value formatter provider containing all value formatters available in global MediaWiki
		 * context.
		 * @since 0.1
		 *
		 * @type {valueFormatters.ValueFormatterFactory}
		 */
		this.valueFormatterProvider = valueFormatterProvider;
	} )();

	// 'valueFormatterProvider' is a required option in the original jQuery.valueview widget
	// implementation. However, if valueFormatters is used in MediaWiki context, then the option
	// should not be required anymore and default to the ValueFormatterFactory object set in
	// mw.ext.valueFormatters.
	vv.prototype.options.valueFormatterProvider = valueFormatterProvider;

}( mediaWiki, dataValues, valueFormatters, jQuery.valueview ) );

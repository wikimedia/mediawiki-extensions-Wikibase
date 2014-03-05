/**
 * Entrypoint for MediaWiki "ValueFormatters" extension JavaScript code.
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, dv, vf ) {
	'use strict';

	mw.ext = mw.ext || {};

	var valueFormatterProvider = new vf.ValueFormatterFactory( vf.NullFormatter );

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

}( mediaWiki, dataValues, valueFormatters ) );

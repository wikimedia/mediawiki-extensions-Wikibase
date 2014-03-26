/**
 * Entrypoint for MediaWiki "ValueView" extension JavaScript code.
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, dv, vv ) {
	'use strict';

	mw.ext = mw.ext || {};

	var expertProvider = new vv.ExpertFactory( vv.experts.UnsupportedValue );

	expertProvider.registerDataValueExpert(
		vv.experts.StringValue,
		dv.StringValue.TYPE
	);

	expertProvider.registerDataValueExpert(
		vv.experts.GlobeCoordinateInput,
		dv.GlobeCoordinateValue.TYPE
	);

	expertProvider.registerDataValueExpert(
		vv.experts.StringValue,
		dv.QuantityValue.TYPE
	);

	expertProvider.registerDataValueExpert(
		vv.experts.TimeInput,
		dv.TimeValue.TYPE
	);

	/**
	 * Object representing the MediaWiki "ValueView" extension.
	 * @since 0.1
	 */
	mw.ext.valueView = new ( function MwExtValueView() {
		/**
		 * Expert provider containing all jQuery.valueview experts available in MediaWiki context.
		 * @since 0.1
		 *
		 * @type {jQuery.valueview.ExpertFactory}
		 */
		this.expertProvider = expertProvider;
	} )();

	// "expertProvider", "valueParserProvider" and "valueFormatterProvider" are required options of
	// the jQuery.valueview widget.
	// If valueview is used in MediaWiki context, these option should not be required anymore and
	// default to the automatically generated providers.
	vv.prototype.options.expertProvider = expertProvider;
	vv.prototype.options.valueParserProvider = mw.ext.valueParsers.valueParserProvider;
	vv.prototype.options.valueFormatterProvider = mw.ext.valueFormatters.valueFormatterProvider;

}( mediaWiki, dataValues, jQuery.valueview ) );

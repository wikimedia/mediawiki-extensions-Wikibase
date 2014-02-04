/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, dv, dt ) {
	'use strict';

	// Register Wikibase specific formatter:

	mw.ext.valueFormatters.valueFormatterProvider.registerDataValueFormatter(
		wb.formatters.ApiBasedValueFormatter,
		dv.QuantityValue.TYPE
	);

	mw.ext.valueFormatters.valueFormatterProvider.registerDataValueFormatter(
		wb.formatters.ApiBasedValueFormatter,
		dv.TimeValue.TYPE
	);

	var commonsMedia = dt.getDataType( 'commonsMedia' );
	if( commonsMedia ) {
		mw.ext.valueFormatters.valueFormatterProvider.registerDataTypeFormatter(
			wb.formatters.ApiBasedValueFormatter,
			commonsMedia.getId()
		);
	}

}( mediaWiki, wikibase, dataValues, dataTypes ) );

/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, dv, dt ) {
	'use strict';

	// Register Wikibase specific formatters:

	mw.ext.valueFormatters.valueFormatterProvider.registerDataValueFormatter(
		wb.formatters.ApiBasedValueFormatter,
		wb.EntityId.TYPE
	);

	mw.ext.valueFormatters.valueFormatterProvider.registerDataValueFormatter(
		wb.formatters.ApiBasedValueFormatter,
		dv.GlobeCoordinateValue.TYPE
	);

	mw.ext.valueFormatters.valueFormatterProvider.registerDataValueFormatter(
		wb.formatters.ApiBasedValueFormatter,
		dv.QuantityValue.TYPE
	);

	mw.ext.valueFormatters.valueFormatterProvider.registerDataValueFormatter(
		wb.formatters.ApiBasedValueFormatter,
		dv.StringValue.TYPE
	);

	mw.ext.valueFormatters.valueFormatterProvider.registerDataValueFormatter(
		wb.formatters.ApiBasedValueFormatter,
		dv.TimeValue.TYPE
	);

	var commonsMediaType = dt.getDataType( 'commonsMedia' );
	if( commonsMediaType ) {
		mw.ext.valueFormatters.valueFormatterProvider.registerDataTypeFormatter(
			wb.formatters.ApiBasedValueFormatter,
			commonsMediaType.getId()
		);
	}

	var urlType = dt.getDataType( 'url' );
	if( urlType ) {
		mw.ext.valueFormatters.valueFormatterProvider.registerDataTypeFormatter(
			wb.formatters.ApiBasedValueFormatter,
			urlType.getId()
		);
	}

}( mediaWiki, wikibase, dataValues, dataTypes ) );

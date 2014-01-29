/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, dv ) {
	'use strict';

	// Register Wikibase specific formatter:

	mw.ext.valueFormatters.valueFormatterProvider.registerDataValueFormatter(
		wb.formatters.QuantityFormatter,
		dv.QuantityValue.TYPE
	);

	mw.ext.valueFormatters.valueFormatterProvider.registerDataTypeFormatter(
		wb.formatters.ApiBasedValueFormatter,
		wb.dataTypes.getDataType( 'commonsMedia' )
	);

	mw.ext.valueFormatters.valueFormatterProvider.registerDataValueFormatter(
		wb.formatters.ApiBasedValueFormatter,
		wb.EntityId.TYPE
	);

}( mediaWiki, wikibase, dataValues ) );

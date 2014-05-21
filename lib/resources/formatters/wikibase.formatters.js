/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
wikibase.formatters = wikibase.formatters || {};

wikibase.formatters.store = ( function( wb, vf, dv, dataTypeStore ) {
	'use strict';

	var formatterStore = new vf.ValueFormatterStore( vf.NullFormatter );

	formatterStore.registerDataValueFormatter(
		wb.formatters.ApiBasedValueFormatter,
		wb.EntityId.TYPE
	);

	formatterStore.registerDataValueFormatter(
		wb.formatters.ApiBasedValueFormatter,
		dv.GlobeCoordinateValue.TYPE
	);

	formatterStore.registerDataValueFormatter(
		wb.formatters.ApiBasedValueFormatter,
		dv.QuantityValue.TYPE
	);

	formatterStore.registerDataValueFormatter(
		wb.formatters.ApiBasedValueFormatter,
		dv.StringValue.TYPE
	);

	formatterStore.registerDataValueFormatter(
		wb.formatters.ApiBasedValueFormatter,
		dv.TimeValue.TYPE
	);

	var commonsMediaType = dataTypeStore.getDataType( 'commonsMedia' );
	if( commonsMediaType ) {
		formatterStore.registerDataTypeFormatter(
			wb.formatters.ApiBasedValueFormatter,
			commonsMediaType.getId()
		);
	}

	var urlType = dataTypeStore.getDataType( 'url' );
	if( urlType ) {
		formatterStore.registerDataTypeFormatter(
			wb.formatters.ApiBasedValueFormatter,
			urlType.getId()
		);
	}

	var monolingualTextType = dataTypeStore.getDataType( 'monolingualtext' );
	if( monolingualTextType ) {
		formatterStore.registerDataTypeFormatter(
			wb.formatters.ApiBasedValueFormatter,
			monolingualTextType.getId()
		);
	}

	return formatterStore;

}( wikibase, valueFormatters, dataValues, wikibase.dataTypes ) );

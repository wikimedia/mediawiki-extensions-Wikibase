/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, vf, dv ) {
	'use strict';

	wb.formatters = wb.formatters || {};

	/**
	 * @param {wikibase.api.RepoApi} api
	 * @param {dataTypes.DataTypeStore} dataTypeStore
	 */
	wb.formatters.getStore = function( api, dataTypeStore ) {
		var apiCaller = new wb.api.FormatValueCaller(
			api,
			dataTypeStore
		);

		var ApiBasedValueFormatter = wb.formatters.getApiBasedValueFormatterConstructor( apiCaller );

		var formatterStore = new vf.ValueFormatterStore( vf.NullFormatter );

		formatterStore.registerDataValueFormatter(
			ApiBasedValueFormatter,
			wb.datamodel.EntityId.TYPE
		);

		formatterStore.registerDataValueFormatter(
			ApiBasedValueFormatter,
			dv.GlobeCoordinateValue.TYPE
		);

		formatterStore.registerDataValueFormatter(
			ApiBasedValueFormatter,
			dv.QuantityValue.TYPE
		);

		formatterStore.registerDataValueFormatter(
			ApiBasedValueFormatter,
			dv.StringValue.TYPE
		);

		formatterStore.registerDataValueFormatter(
			ApiBasedValueFormatter,
			dv.TimeValue.TYPE
		);

		var commonsMediaType = dataTypeStore.getDataType( 'commonsMedia' );
		if( commonsMediaType ) {
			formatterStore.registerDataTypeFormatter(
				ApiBasedValueFormatter,
				commonsMediaType.getId()
			);
		}

		var urlType = dataTypeStore.getDataType( 'url' );
		if( urlType ) {
			formatterStore.registerDataTypeFormatter(
				ApiBasedValueFormatter,
				urlType.getId()
			);
		}

		var monolingualTextType = dataTypeStore.getDataType( 'monolingualtext' );
		if( monolingualTextType ) {
			formatterStore.registerDataTypeFormatter(
				ApiBasedValueFormatter,
				monolingualTextType.getId()
			);
		}

		return formatterStore;
	};

}( wikibase, valueFormatters, dataValues ) );

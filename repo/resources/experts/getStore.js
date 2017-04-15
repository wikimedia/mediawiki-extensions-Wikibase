/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, vv, dv ) {
'use strict';

var MODULE = wb.experts;

/**
 * @param {dataTypes.DataTypeStore} dataTypeStore
 */
MODULE.getStore = function( dataTypeStore ) {
	var expertStore = new vv.ExpertStore( vv.experts.UnsupportedValue );

	expertStore.registerDataValueExpert(
		vv.experts.GlobeCoordinateInput,
		dv.GlobeCoordinateValue.TYPE
	);

	expertStore.registerDataValueExpert(
		vv.experts.QuantityInput,
		dv.QuantityValue.TYPE
	);

	expertStore.registerDataValueExpert(
		vv.experts.StringValue,
		dv.StringValue.TYPE
	);

	expertStore.registerDataValueExpert(
		vv.experts.TimeInput,
		dv.TimeValue.TYPE
	);

	expertStore.registerDataValueExpert(
		vv.experts.UnDeserializableValue,
		dv.UnDeserializableValue.TYPE
	);

	// Register experts for data types defined in Wikibase. Since those data types are defined by a
	// setting, it needs to be checked whether they are actually defined.

	var dataTypeIdToExpertConstructor = {
		commonsMedia: vv.experts.CommonsMediaType,
		'geo-shape': vv.experts.GeoShape,
		'tabular-data': vv.experts.TabularData,
		'external-id': vv.experts.StringValue,
		monolingualtext: vv.experts.MonolingualText,
		url: vv.experts.StringValue,
		'wikibase-item': wb.experts.Item,
		'wikibase-property': wb.experts.Property
	};

	for ( var dataTypeId in dataTypeIdToExpertConstructor ) {
		var dataType = dataTypeStore.getDataType( dataTypeId );
		if ( dataType ) {
			expertStore.registerDataTypeExpert(
				dataTypeIdToExpertConstructor[dataTypeId],
				dataType.getId()
			);
		}
	}

	return expertStore;

};

}( wikibase, jQuery.valueview, dataValues ) );

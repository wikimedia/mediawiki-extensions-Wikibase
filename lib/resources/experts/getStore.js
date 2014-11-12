/**
 * @licence GNU GPL v2+
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
		wb.experts.EntityIdInput,
		wb.datamodel.EntityId.TYPE
	);

	expertStore.registerDataValueExpert(
		vv.experts.GlobeCoordinateInput,
		dv.GlobeCoordinateValue.TYPE
	);

	expertStore.registerDataValueExpert(
		vv.experts.StringValue,
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

	// Register experts for data types defined in Wikibase. Since those data types are defined by a
	// setting, it needs to be checked whether they are actually defined.

	var commonsMediaType = dataTypeStore.getDataType( 'commonsMedia' );
	if( commonsMediaType ) {
		expertStore.registerDataTypeExpert(
			vv.experts.CommonsMediaType,
			commonsMediaType.getId()
		);
	}

	var urlType = dataTypeStore.getDataType( 'url' );
	if( urlType ) {
		expertStore.registerDataTypeExpert(
			vv.experts.StringValue,
			urlType.getId()
		);
	}

	var monoTextType = dataTypeStore.getDataType( 'monolingualtext' );
	if( monoTextType ) {
		expertStore.registerDataTypeExpert(
			vv.experts.MonolingualText,
			monoTextType.getId()
		);
	}

	return expertStore;

};

} ( wikibase, jQuery.valueview, dataValues ) );

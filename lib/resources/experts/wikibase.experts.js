/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
wikibase.experts = ( function( wb, dataTypeStore, vv, dv ) {
	'use strict';

	var expertStore = new vv.ExpertFactory( vv.experts.UnsupportedValue );

	dataTypeStore.registerDataValueExpert(
		wb.experts.EntityIdInput,
		wb.EntityId.TYPE
	);

	expertStore.registerDataValueExpert(
		vv.experts.StringValue,
		dv.StringValue.TYPE
	);

	expertStore.registerDataValueExpert(
		vv.experts.GlobeCoordinateValue,
		dv.GlobeCoordinateValue.TYPE
	);

	expertStore.registerDataValueExpert(
		vv.experts.TimeValue,
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
			vv.experts.UrlType,
			urlType.getId()
		);
	}

	return expertStore;

}( wikibase, wikibase.dataTypes, jQuery.valueview, dataValues ) );

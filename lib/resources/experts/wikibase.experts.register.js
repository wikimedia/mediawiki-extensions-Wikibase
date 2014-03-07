/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, dataTypeStore, vv ) {
	'use strict';

	mw.ext.valueView.expertProvider.registerDataValueExpert(
		wb.experts.EntityIdInput,
		wb.EntityId.TYPE
	);

	// Register experts for data types defined in Wikibase. Since those data types are defined by a
	// setting, it needs to be checked whether they are actually defined.

	var commonsMediaType = dataTypeStore.getDataType( 'commonsMedia' );
	if( commonsMediaType ) {
		mw.ext.valueView.expertProvider.registerDataTypeExpert(
			vv.experts.CommonsMediaType,
			commonsMediaType.getId()
		);
	}

	var urlType = dataTypeStore.getDataType( 'url' );
	if( urlType ) {
		mw.ext.valueView.expertProvider.registerDataTypeExpert(
			vv.experts.StringValue,
			urlType.getId()
		);
	}

}( mediaWiki, wikibase, wikibase.dataTypes, jQuery.valueview ) );

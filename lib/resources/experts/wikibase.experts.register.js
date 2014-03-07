/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, dt, vv ) {
	'use strict';

	mw.ext.valueView.expertProvider.registerDataValueExpert(
		wb.experts.EntityIdInput,
		wb.EntityId.TYPE
	);

	// Register experts for data types defined in Wikibase. Since those data types are defined by a
	// setting, it needs to be checked whether they are actually defined.

	var commonsMediaType = dt.getDataType( 'commonsMedia' );
	if( commonsMediaType ) {
		mw.ext.valueView.expertProvider.registerDataTypeExpert(
			vv.experts.CommonsMediaType,
			commonsMediaType.getId()
		);
	}

	var urlType = dt.getDataType( 'url' );
	if( urlType ) {
		mw.ext.valueView.expertProvider.registerDataTypeExpert(
			vv.experts.StringValue,
			urlType.getId()
		);
	}

}( mediaWiki, wikibase, dataTypes, jQuery.valueview ) );

/**
 * @license GPL-2.0+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 */
wikibase.dataTypeStore = ( function ( $, mw, wb ) {
	'use strict';

	var dataTypeStore = new wb.dataTypes.DataTypeStore(),
		dataTypeDefinitions = mw.config.get( 'wbDataTypes' ) || {};

	$.each( dataTypeDefinitions, function ( dtTypeId, dtDefinition ) {
		dataTypeStore.registerDataType( wb.dataTypes.DataType.newFromJSON( dtTypeId, dtDefinition ) );
	} );

	return dataTypeStore;

}( jQuery, mediaWiki, wikibase ) );

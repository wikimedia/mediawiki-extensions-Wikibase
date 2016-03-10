/**
 * @license GPL-2.0+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 */
wikibase.dataTypeStore = ( function( $, mw, dataTypes ) {
	'use strict';

	var dataTypeStore = new dataTypes.DataTypeStore(),
		dataTypeDefinitions = mw.config.get( 'wbDataTypes' ) || {};

	$.each( dataTypeDefinitions, function( dtTypeId, dtDefinition ) {
		dataTypeStore.registerDataType( dataTypes.DataType.newFromJSON( dtTypeId, dtDefinition ) );
	} );

	return dataTypeStore;

}( jQuery, mediaWiki, dataTypes ) );

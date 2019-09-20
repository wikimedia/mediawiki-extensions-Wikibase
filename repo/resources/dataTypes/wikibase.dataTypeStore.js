/**
 * @license GPL-2.0-or-later
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 */
wikibase.dataTypeStore = ( function () {
	'use strict';

	var DataTypeStore = require( './DataTypeStore.js' ),
		DataType = require( './DataType.js' ),
		dataTypeStore = new DataTypeStore(),
		dataTypeDefinitions = mw.config.get( 'wbDataTypes' ) || {};

	// eslint-disable-next-line no-jquery/no-each-util
	$.each( dataTypeDefinitions, function ( dtTypeId, dtDefinition ) {
		dataTypeStore.registerDataType( DataType.newFromJSON( dtTypeId, dtDefinition ) );
	} );

	module.exports = dataTypeStore;

}() );

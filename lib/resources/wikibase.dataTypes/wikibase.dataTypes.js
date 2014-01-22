/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
wikibase.dataTypes = ( function( $, mw, dataTypes ) {
	'use strict';

	var dataTypeDefinitions = mw.config.get( 'wbDataTypes' ) || {};

	$.each( dataTypeDefinitions, function( dtTypeId, dtDefinition ) {
		dataTypes.registerDataType( dataTypes.DataType.newFromJSON( dtTypeId, dtDefinition ) );
	} );

	/**
	 * TODO: dataTypes should not be a singleton, instead we should replace it with a instantiable
	 *  factory, so we can use our own instance just for WB data types here.
	 */
	return dataTypes;

}( jQuery, mediaWiki, dataTypes ) );

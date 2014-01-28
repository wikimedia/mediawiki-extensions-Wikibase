/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
wikibase.dataTypes = ( function( $, mw, dataTypes, vv ) {
	'use strict';

	var dataTypeDefinitions = mw.config.get( 'wbDataTypes' ) || {};

	$.each( dataTypeDefinitions, function( dtTypeId, dtDefinition ) {
		dataTypes.registerDataType( dataTypes.DataType.newFromJSON( dtTypeId, dtDefinition ) );
	} );

		// Experts for values for certain data types:
	// Those data types might not be defined, so check for them first.
	var commonsMediaType = dataTypes.getDataType( 'commonsMedia' );
	if( commonsMediaType ) {
	//if( commonsMediaType ) {
		vv.prototype.options.expertProvider.registerExpert(
			commonsMediaType,
			vv.experts.CommonsMediaType
		);
	}

	var urlType = dataTypes.getDataType( 'url' );
	if( urlType ) {
		vv.prototype.options.expertProvider.registerExpert(
			urlType,
			vv.experts.UrlType
		);
	}

	/**
	 * TODO: dataTypes should not be a singleton, instead we should replace it with a instantiable
	 *  factory, so we can use our own instance just for WB data types here.
	 */
	return dataTypes;

}( jQuery, mediaWiki, dataTypes, jQuery.valueview ) );

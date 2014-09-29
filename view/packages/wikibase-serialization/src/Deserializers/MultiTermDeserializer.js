/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/**
 * Deserializer for MultiTerm objects.
 *
 * @constructor
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 */
MODULE.MultiTermDeserializer = util.inherit( 'WbMultiTermDeserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Deserializer.deserialize
	 *
	 * @return {wikibase.datamodel.MultiTerm}
	 */
	deserialize: function( serialization ) {
		if( !serialization.length ) {
			throw new Error( 'Unable to deserialize empty serialization to MultiTerm' );
		}

		var languageCode = serialization[0].language,
			terms = [];

		for( var i = 0; i < serialization.length; i++ ) {
			terms.push( serialization[i].value );
		}

		return new wb.datamodel.MultiTerm( languageCode, terms );
	}
} );

}( wikibase, util ) );

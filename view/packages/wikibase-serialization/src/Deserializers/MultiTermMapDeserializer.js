/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/**
 * @constructor
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 */
MODULE.MultiTermMapDeserializer = util.inherit( 'WbMultiTermMapDeserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Deserializer.deserialize
	 *
	 * @return {wikibase.datamodel.MultiTermMap}
	 */
	deserialize: function( serialization ) {
		var multiTerms = {},
			multiTermDeserializer = new MODULE.MultiTermDeserializer();

		for( var languageCode in serialization ) {
			multiTerms[languageCode]
				= multiTermDeserializer.deserialize( serialization[languageCode] );
		}

		return new wb.datamodel.MultiTermMap( multiTerms );
	}
} );

}( wikibase, util ) );

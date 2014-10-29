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
MODULE.TermMapDeserializer = util.inherit( 'WbTermMapDeserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Deserializer.deserialize
	 *
	 * @return {wikibase.datamodel.TermMap}
	 */
	deserialize: function( serialization ) {
		var terms = {},
			termDeserializer = new MODULE.TermDeserializer();

		for( var languageCode in serialization ) {
			terms[languageCode] = termDeserializer.deserialize( serialization[languageCode] );
		}

		return new wb.datamodel.TermMap( terms );
	}
} );

}( wikibase, util ) );

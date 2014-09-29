/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/* Deserializer for MultiTermSet objects.
 *
 * @constructor
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 */
MODULE.MultiTermSetDeserializer = util.inherit( 'WbMultiTermSetDeserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Deserializer.deserialize
	 *
	 * @return {wikibase.datamodel.MultiTermSet}
	 */
	deserialize: function( serialization ) {
		var multiTerms = [],
			multiTermDeserializer = new MODULE.MultiTermDeserializer();

		for( var languageCode in serialization ) {
			multiTerms.push( multiTermDeserializer.deserialize( serialization[languageCode] ) );
		}

		return new wb.datamodel.MultiTermSet( multiTerms );
	}
} );

}( wikibase, util ) );

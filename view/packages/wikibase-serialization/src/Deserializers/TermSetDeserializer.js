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
MODULE.TermSetDeserializer = util.inherit( 'WbTermSetDeserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Deserializer.deserialize
	 *
	 * @return {wikibase.datamodel.TermSet}
	 */
	deserialize: function( serialization ) {
		var terms = [],
			termDeserializer = new MODULE.TermDeserializer();

		for( var languageCode in serialization ) {
			terms.push( termDeserializer.deserialize( serialization[languageCode] ) );
		}

		return new wb.datamodel.TermSet( terms );
	}
} );

}( wikibase, util ) );

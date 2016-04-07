( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/**
 * @class wikibase.serialization.TermMapDeserializer
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.TermMapDeserializer = util.inherit( 'WbTermMapDeserializer', PARENT, {
	/**
	 * @inheritdoc
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

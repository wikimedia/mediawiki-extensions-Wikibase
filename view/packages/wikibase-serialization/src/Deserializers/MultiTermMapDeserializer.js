( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/**
 * @class wikibase.serialization.MultiTermMapDeserializer
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.MultiTermMapDeserializer = util.inherit( 'WbMultiTermMapDeserializer', PARENT, {
	/**
	 * @inheritdoc
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

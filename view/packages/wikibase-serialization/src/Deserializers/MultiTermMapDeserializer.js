( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer,
	MultiTermDeserializer = require( './MultiTermDeserializer.js' );

/**
 * @class wikibase.serialization.MultiTermMapDeserializer
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
module.exports = util.inherit( 'WbMultiTermMapDeserializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @return {wikibase.datamodel.MultiTermMap}
	 */
	deserialize: function( serialization ) {
		var multiTerms = {},
			multiTermDeserializer = new MultiTermDeserializer();

		for( var languageCode in serialization ) {
			multiTerms[languageCode]
				= multiTermDeserializer.deserialize( serialization[languageCode] );
		}

		return new wb.datamodel.MultiTermMap( multiTerms );
	}
} );

}( wikibase, util ) );

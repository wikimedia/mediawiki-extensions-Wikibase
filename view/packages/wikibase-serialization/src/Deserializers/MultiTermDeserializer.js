( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/**
 * @class wikibase.serialization.MultiTermDeserializer
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.MultiTermDeserializer = util.inherit( 'WbMultiTermDeserializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @return {wikibase.datamodel.MultiTerm}
	 *
	 * @throws {Error} if serialization is empty.
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

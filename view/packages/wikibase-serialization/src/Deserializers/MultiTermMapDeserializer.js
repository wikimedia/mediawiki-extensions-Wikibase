( function() {
	'use strict';

	var PARENT = require( './Deserializer.js' ),
		datamodel = require( 'wikibase.datamodel' ),
		MultiTermDeserializer = require( './MultiTermDeserializer.js' );

	/**
	 * @class MultiTermMapDeserializer
	 * @extends Deserializer
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
		 * @return {datamodel.MultiTermMap}
		 */
		deserialize: function( serialization ) {
			var multiTerms = {},
				multiTermDeserializer = new MultiTermDeserializer();

			for( var languageCode in serialization ) {
				multiTerms[languageCode] = multiTermDeserializer.deserialize( serialization[languageCode] );
			}

			return new datamodel.MultiTermMap( multiTerms );
		}
	} );

}() );

( function() {
	'use strict';

	var PARENT = require( './Deserializer.js' ),
		TermDeserializer = require( './TermDeserializer.js' ),
		datamodel = require( 'wikibase.datamodel' );

	/**
	 * @class TermMapDeserializer
	 * @extends Deserializer
	 * @since 2.0
	 * @license GPL-2.0+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 */
	module.exports = util.inherit( 'WbTermMapDeserializer', PARENT, {
		/**
		 * @inheritdoc
		 *
		 * @return {datamodel.TermMap}
		 */
		deserialize: function( serialization ) {
			var terms = {},
				termDeserializer = new TermDeserializer();

			for( var languageCode in serialization ) {
				terms[languageCode] = termDeserializer.deserialize( serialization[languageCode] );
			}

			return new datamodel.TermMap( terms );
		}
	} );

}() );

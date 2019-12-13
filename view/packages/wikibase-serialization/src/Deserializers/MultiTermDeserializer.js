( function() {
	'use strict';

	var PARENT = require( './Deserializer.js' ),
		datamodel = require( 'wikibase.datamodel' );

	/**
	 * @class MultiTermDeserializer
	 * @extends Deserializer
	 * @since 2.0
	 * @license GPL-2.0+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 */
	module.exports = util.inherit( 'WbMultiTermDeserializer', PARENT, {
		/**
		 * @inheritdoc
		 *
		 * @return {datamodel.MultiTerm}
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

			return new datamodel.MultiTerm( languageCode, terms );
		}
	} );

}() );

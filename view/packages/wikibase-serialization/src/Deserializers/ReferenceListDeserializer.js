( function() {
	'use strict';

	var PARENT = require( './Deserializer.js' ),
		datamodel = require( 'wikibase.datamodel' ),
		ReferenceDeserializer = require( './ReferenceDeserializer.js' );

	/**
	 * @class ReferenceListDeserializer
	 * @extends Deserializer
	 * @since 2.0
	 * @license GPL-2.0+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 */
	module.exports = util.inherit( 'WbReferenceListDeserializer', PARENT, {
		/**
		 * @inheritdoc
		 *
		 * @return {datamodel.ReferenceList}
		 */
		deserialize: function( serialization ) {
			var references = [],
				referenceDeserializer = new ReferenceDeserializer();

			for( var i = 0; i < serialization.length; i++ ) {
				references.push( referenceDeserializer.deserialize( serialization[i] ) );
			}

			return new datamodel.ReferenceList( references );
		}
	} );

}() );

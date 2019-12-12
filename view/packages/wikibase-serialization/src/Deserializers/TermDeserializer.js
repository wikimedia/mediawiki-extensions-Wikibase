( function() {
	'use strict';

	var PARENT = require( './Deserializer.js' ),
		datamodel = require( 'wikibase.datamodel' );

	/**
	 * @class TermDeserializer
	 * @extends Deserializer
	 * @since 2.0
	 * @license GPL-2.0+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 */
	module.exports = util.inherit( 'WbTermDeserializer', PARENT, {
		/**
		 * @inheritdoc
		 *
		 * @return {datamodel.Term}
		 */
		deserialize: function( serialization ) {
			return new datamodel.Term( serialization.language, serialization.value );
		}
	} );

}() );

( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer,
	datamodel = require( 'wikibase.datamodel' );

/**
 * @class wikibase.serialization.TermDeserializer
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.TermDeserializer = util.inherit( 'WbTermDeserializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @return {datamodel.Term}
	 */
	deserialize: function( serialization ) {
		return new datamodel.Term( serialization.language, serialization.value );
	}
} );

}( wikibase, util ) );

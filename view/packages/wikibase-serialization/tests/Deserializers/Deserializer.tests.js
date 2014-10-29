/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.Deserializer' );

QUnit.test( 'deserialize()', function( assert ) {
	var SomeDeserializer = util.inherit( 'WbTestDeserializer', wb.serialization.Deserializer, {} ),
		someDeserializer = new SomeDeserializer();

	assert.throws(
		function() {
			someDeserializer.deserialize( {} );
		},
		'Trying to deserialize on a Deserializer not having deserialize() specified fails.'
	);
} );

}( wikibase, util, QUnit ) );

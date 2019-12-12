/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function() {
	'use strict';

	QUnit.module( 'Deserializer' );

	var Deserializer = require( '../../src/Deserializers/Deserializer.js' );

	QUnit.test( 'deserialize()', function( assert ) {
		assert.expect( 1 );
		var SomeDeserializer = util.inherit( 'WbTestDeserializer', Deserializer, {} ),
			someDeserializer = new SomeDeserializer();

		assert.throws(
			function() {
				someDeserializer.deserialize( {} );
			},
			'Trying to deserialize on a Deserializer not having deserialize() specified fails.'
		);
	} );

}() );

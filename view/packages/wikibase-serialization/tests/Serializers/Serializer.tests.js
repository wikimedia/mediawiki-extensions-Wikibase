/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function() {
	'use strict';

	QUnit.module( 'Serializer' );

	var Serializer = require( '../../src/Serializers/Serializer.js' );

	QUnit.test( 'serialize()', function( assert ) {
		assert.expect( 1 );
		var SomeSerializer = util.inherit( 'WbTestSerializer', Serializer, {} ),
			someSerializer = new SomeSerializer();

		assert.throws(
			function() {
				someSerializer.serialize( {} );
			},
			'Trying to serialize on a Serializer not having serialize() specified fails.'
		);
	} );

}() );

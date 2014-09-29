/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, wb, util, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.DeserializerFactory' );

QUnit.test( 'registerDeserializer(), newDeserializerFor()', function( assert ) {
	var deserializerFactory = new wb.serialization.DeserializerFactory();

	var testSets = [
		{
			Constructor: function Constructor1() {},
			Deserializer: util.inherit( 'WbTestDeserializer1', wb.serialization.Deserializer, {} )
		}, {
			Constructor: function Constructor2() {},
			Deserializer: util.inherit( 'WbTestDeserializer2', wb.serialization.Deserializer, {} )
		}
	];

	assert.throws(
		function() {
			deserializerFactory.registerDeserializer( testSets[0].Deserializer );
		},
		'Throwing error when omitting constructor a Deserializer should be registered for.'
	);

	assert.throws(
		function() {
			deserializerFactory.registerDeserializer( function() {}, testSets[0].Constructor );
		},
		'Throwing error when Deserializer is not deriving from Deserializer base constructor.'
	);

	deserializerFactory.registerDeserializer( testSets[0].Deserializer, testSets[0].Constructor );
	deserializerFactory.registerDeserializer( testSets[1].Deserializer, testSets[1].Constructor );

	assert.ok(
		deserializerFactory.newDeserializerFor( testSets[0].Constructor ).constructor
			=== testSets[0].Deserializer,
		'Retrieved Deserializer.'
	);

	assert.throws(
		function() {
			deserializerFactory.newDeserializerFor( 'string' );
		},
		'Throwing error when not passing a valid parameter to newSerializerFor().'
	);

	assert.throws(
		function() {
			deserializerFactory.newDeserializerFor( function() {} );
		},
		'Throwing error when no Deserializer is registered for a constructor.'
	);
} );

}( jQuery, wikibase, util, QUnit ) );

/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( $, wb, util, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.SerializerFactory' );

var SerializerFactory = wb.serialization.SerializerFactory,
	serializerFactory = new SerializerFactory();

QUnit.test( 'registerSerializer(), newSerializerFor()', function( assert ) {
	var testSets = [
		{
			Constructor: function Constructor1() {},
			Serializer: util.inherit( 'WbTestSerializer1', wb.serialization.Serializer, {} )
		}, {
			Constructor: function Constructor2() {},
			Serializer: util.inherit( 'WbTestSerializer2', wb.serialization.Serializer, {} )
		}
	];

	assert.throws(
		function() {
			SerializerFactory.registerSerializer( testSets[0].Serializer );
		},
		'Throwing error when omitting constructor a serializer should be registered for.'
	);

	assert.throws(
		function() {
			SerializerFactory.registerSerializer( function() {}, testSets[0].Constructor );
		},
		'Throwing error when serializer is not deriving from Serializer base constructor.'
	);

	SerializerFactory.registerSerializer( testSets[0].Serializer, testSets[0].Constructor );
	SerializerFactory.registerSerializer( testSets[1].Serializer, testSets[1].Constructor );

	assert.ok(
		serializerFactory.newSerializerFor( testSets[0].Constructor ).constructor
			=== testSets[0].Serializer,
		'Retrieved serializer by constructor.'
	);

	assert.ok(
		serializerFactory.newSerializerFor( new( testSets[0].Constructor ) ).constructor
			=== testSets[0].Serializer,
		'Retrieved serializer by object.'
	);

	var options = { someOption: 'someOption' },
		serializer = serializerFactory.newSerializerFor( testSets[1].Constructor, options );

	assert.deepEqual(
		serializer.getOptions(),
		options,
		'Passed options on to serializer.'
	);

	assert.throws(
		function() {
			serializerFactory.newSerializerFor( 'string' );
		},
		'Throwing error when not passing a valid parameter to newSerializerFor().'
	);

	assert.throws(
		function() {
			serializerFactory.newSerializerFor( function() {} );
		},
		'Throwing error when no serializer is registered for a constructor.'
	);
} );

QUnit.test( 'registerDeserializer(), newDeserializerFor()', function( assert ) {
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
			SerializerFactory.registerDeserializer( testSets[0].Deserializer );
		},
		'Throwing error when omitting constructor a Deserializer should be registered for.'
	);

	assert.throws(
		function() {
			SerializerFactory.registerDeserializer( function() {}, testSets[0].Constructor );
		},
		'Throwing error when Deserializer is not deriving from Deserializer base constructor.'
	);

	SerializerFactory.registerDeserializer( testSets[0].Deserializer, testSets[0].Constructor );
	SerializerFactory.registerDeserializer( testSets[1].Deserializer, testSets[1].Constructor );

	assert.ok(
		serializerFactory.newDeserializerFor( testSets[0].Constructor ).constructor
			=== testSets[0].Deserializer,
		'Retrieved Deserializer.'
	);

	var options = { someOption: 'someOption' },
		deserializer = serializerFactory.newDeserializerFor( testSets[1].Constructor, options );

	assert.deepEqual(
		deserializer.getOptions(),
		options,
		'Passed options on to Deserializer.'
	);

	assert.throws(
		function() {
			serializerFactory.newDeserializerFor( 'string' );
		},
		'Throwing error when not passing a valid parameter to newSerializerFor().'
	);

	assert.throws(
		function() {
			serializerFactory.newDeserializerFor( function() {} );
		},
		'Throwing error when no Deserializer is registered for a constructor.'
	);
} );

}( jQuery, wikibase, util, QUnit ) );

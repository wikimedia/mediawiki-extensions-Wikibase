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

QUnit.test( 'registerUnserializer(), newUnserializerFor()', function( assert ) {
	var testSets = [
		{
			Constructor: function Constructor1() {},
			Unserializer: util.inherit( 'WbTestUnserializer1', wb.serialization.Unserializer, {} )
		}, {
			Constructor: function Constructor2() {},
			Unserializer: util.inherit( 'WbTestUnserializer2', wb.serialization.Unserializer, {} )
		}
	];

	assert.throws(
		function() {
			SerializerFactory.registerUnserializer( testSets[0].Unserializer );
		},
		'Throwing error when omitting constructor an unserializer should be registered for.'
	);

	assert.throws(
		function() {
			SerializerFactory.registerUnserializer( function() {}, testSets[0].Constructor );
		},
		'Throwing error when unserializer is not deriving from Unserializer base constructor.'
	);

	SerializerFactory.registerUnserializer( testSets[0].Unserializer, testSets[0].Constructor );
	SerializerFactory.registerUnserializer( testSets[1].Unserializer, testSets[1].Constructor );

	assert.ok(
		serializerFactory.newUnserializerFor( testSets[0].Constructor ).constructor
			=== testSets[0].Unserializer,
		'Retrieved unserializer.'
	);

	var options = { someOption: 'someOption' },
		unserializer = serializerFactory.newUnserializerFor( testSets[1].Constructor, options );

	assert.deepEqual(
		unserializer.getOptions(),
		options,
		'Passed options on to unserializer.'
	);

	assert.throws(
		function() {
			serializerFactory.newUnserializerFor( 'string' );
		},
		'Throwing error when not passing a valid parameter to newSerializerFor().'
	);

	assert.throws(
		function() {
			serializerFactory.newUnserializerFor( function() {} );
		},
		'Throwing error when no unserializer is registered for a constructor.'
	);
} );

}( jQuery, wikibase, util, QUnit ) );

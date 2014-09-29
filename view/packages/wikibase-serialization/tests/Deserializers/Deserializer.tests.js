/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( $, wb, util, QUnit ) {
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

QUnit.test( 'setOptions(), getOptions()', function( assert ) {
	var SomeDeserializer = util.inherit( 'WbTestDeserializer', wb.serialization.Deserializer, {} ),
		options = {
			someOption: 'someValue'
		},
		someDeserializer = new SomeDeserializer( options );

	assert.deepEqual(
		someDeserializer.getOptions( 'someOption' ),
		options,
		'Retrieved options.'
	);

	options = {
		newOption1: 'newValue1',
		newOption2: 'newValue2'
	};

	someDeserializer.setOptions( options );

	var originalOptions = $.extend( {}, options ),
		retrievedOptions = someDeserializer.getOptions();

	assert.deepEqual(
		retrievedOptions,
		options,
		'Set and retrieved new options.'
	);

	options.addedOption = 'addedOption';

	assert.deepEqual(
		someDeserializer.getOptions(),
		originalOptions,
		'Altering original options object does not change options stored internally.'
	);

	retrievedOptions.addedOption = 'addedOption';

	assert.deepEqual(
		someDeserializer.getOptions(),
		originalOptions,
		'Altering retrieved options does not change options stored internally.'
	);

	someDeserializer.setOptions( {} );

	assert.deepEqual(
		someDeserializer.getOptions(),
		{},
		'Emptied options.'
	);

	assert.throws(
		function() {
			someDeserializer.setOptions( 'someOption' );
		},
		'Trying to pass a non-object to setOptions fails.'
	);
} );

}( jQuery, wikibase, util, QUnit ) );

/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( $, wb, util, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.Unserializer' );

QUnit.test( 'unserialize()', function( assert ) {
	var SomeUnserializer = util.inherit( 'WbTestUnserializer', wb.serialization.Unserializer, {} ),
		someUnserializer = new SomeUnserializer();

	assert.throws(
		function() {
			someUnserializer.unserialize( {} );
		},
		'Trying to unserialize on a Unserializer not having unserialize() specified fails.'
	);
} );

QUnit.test( 'setOptions(), getOptions()', function( assert ) {
	var SomeUnserializer = util.inherit( 'WbTestUnserializer', wb.serialization.Unserializer, {} ),
		options = {
			someOption: 'someValue'
		},
		someUnserializer = new SomeUnserializer( options );

	assert.deepEqual(
		someUnserializer.getOptions( 'someOption' ),
		options,
		'Retrieved options.'
	);

	options = {
		newOption1: 'newValue1',
		newOption2: 'newValue2'
	};

	someUnserializer.setOptions( options );

	var originalOptions = $.extend( {}, options ),
		retrievedOptions = someUnserializer.getOptions();

	assert.deepEqual(
		retrievedOptions,
		options,
		'Set and retrieved new options.'
	);

	options.addedOption = 'addedOption';

	assert.deepEqual(
		someUnserializer.getOptions(),
		originalOptions,
		'Altering original options object does not change options stored internally.'
	);

	retrievedOptions.addedOption = 'addedOption';

	assert.deepEqual(
		someUnserializer.getOptions(),
		originalOptions,
		'Altering retrieved options does not change options stored internally.'
	);

	someUnserializer.setOptions( {} );

	assert.deepEqual(
		someUnserializer.getOptions(),
		{},
		'Emptied options.'
	);

	assert.throws(
		function() {
			someUnserializer.setOptions( 'someOption' );
		},
		'Trying to pass a non-object to setOptions fails.'
	);
} );

}( jQuery, wikibase, util, QUnit ) );

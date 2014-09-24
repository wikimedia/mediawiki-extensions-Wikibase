/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( $, wb, util, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.Serializer' );

QUnit.test( 'serialize()', function( assert ) {
	var SomeSerializer = util.inherit( 'WbTestSerializer', wb.serialization.Serializer, {} ),
		someSerializer = new SomeSerializer();

	assert.throws(
		function() {
			someSerializer.serialize( {} );
		},
		'Trying to serialize on a Serializer not having serialize() specified fails.'
	);
} );

QUnit.test( 'setOptions(), getOptions()', function( assert ) {
	var SomeSerializer = util.inherit( 'WbTestSerializer', wb.serialization.Serializer, {} ),
		options = {
			someOption: 'someValue'
		},
		someSerializer = new SomeSerializer( options );

	assert.deepEqual(
		someSerializer.getOptions( 'someOption' ),
		options,
		'Retrieved options.'
	);

	options = {
		newOption1: 'newValue1',
		newOption2: 'newValue2'
	};

	someSerializer.setOptions( options );

	var originalOptions = $.extend( {}, options ),
		retrievedOptions = someSerializer.getOptions();

	assert.deepEqual(
		retrievedOptions,
		options,
		'Set and retrieved new options.'
	);

	options.addedOption = 'addedOption';

	assert.deepEqual(
		someSerializer.getOptions(),
		originalOptions,
		'Altering original options object does not change options stored internally.'
	);

	retrievedOptions.addedOption = 'addedOption';

	assert.deepEqual(
		someSerializer.getOptions(),
		originalOptions,
		'Altering retrieved options does not change options stored internally.'
	);

	someSerializer.setOptions( {} );

	assert.deepEqual(
		someSerializer.getOptions(),
		{},
		'Emptied options.'
	);

	assert.throws(
		function() {
			someSerializer.setOptions( 'someOption' );
		},
		'Trying to pass a non-object to setOptions fails.'
	);
} );

}( jQuery, wikibase, util, QUnit ) );

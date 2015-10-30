/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, QUnit, wb, dt, mw ) {
'use strict';

QUnit.module( 'jquery.wikibase.snakview', QUnit.newMwEnvironment( {
	teardown: function() {
		$( '.test_snakview' ).each( function() {
			var $snakview = $( this ),
				snakview = $snakview.data( 'snakview' );

			if ( snakview ) {
				snakview.destroy();
			}

			$snakview.remove();
		} );
	}
} ) );

var snakSerializer = new wb.serialization.SnakSerializer(),
	snakDeserializer = new wb.serialization.SnakDeserializer();

/**
 * @param {Object} [options={}]
 * @param {jQuery} [$node]
 * @return {jQuery}
 */
var createSnakview = function( options, $node ) {
	options = $.extend( {
		autoStartEditing: false,
		entityIdHtmlFormatter: {
			format: function() {
				return $.Deferred().resolve( 'Label' ).promise();
			}
		},
		entityIdPlainFormatter: {
			format: function( entityId ) {
				return $.Deferred().resolve( entityId ).promise();
			}
		},
		entityStore: {
			get: function( entityId ) {
				return $.Deferred().resolve().promise();
			}
		},
		valueViewBuilder: 'I am a ValueViewBuilder',
		dataTypeStore: new dt.DataTypeStore()
	}, options || {} );

	$node = $node || $( '<div/>' ).appendTo( 'body' );

	return $node
		.addClass( 'test_snakview' )
		.snakview( options );
};

QUnit.test( 'Create & destroy', function( assert ) {
	assert.expect( 6 );
	var $snakview = createSnakview(),
		snakview = $snakview.data( 'snakview' );

	assert.ok(
		snakview instanceof $.wikibase.snakview,
		'Created widget.'
	);

	snakview.destroy();

	assert.ok(
		$snakview.data( 'snakview' ) === undefined,
		'Destroyed widget.'
	);

	$snakview = createSnakview( {
		value: new wb.datamodel.PropertyNoValueSnak( 'P1' )
	} );
	snakview = $snakview.data( 'snakview' );

	assert.ok(
		snakview !== undefined,
		'Created widget passing a wikibase.datamodel.Snak object.'
	);

	snakview.destroy();

	assert.ok(
		$snakview.data( 'snakview' ) === undefined,
		'Destroyed widget.'
	);

	$snakview = createSnakview( {
		value: snakSerializer.serialize( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
	} );
	snakview = $snakview.data( 'snakview' );

	assert.ok(
		snakview !== undefined,
		'Created widget passing a Snak serialization.'
	);

	snakview.destroy();

	assert.ok(
		$snakview.data( 'snakview' ) === undefined,
		'Destroyed widget.'
	);
} );

QUnit.test( 'value()', function( assert ) {
	assert.expect( 7 );
	var $snakview = createSnakview(),
		snakview = $snakview.data( 'snakview' );

	assert.deepEqual(
		snakview.value(),
		{
			snaktype: wb.datamodel.PropertyValueSnak.TYPE
		},
		'Verified default value.'
	);

	var newValue = {
		property: 'P1',
		snaktype: wb.datamodel.PropertySomeValueSnak.TYPE
	};

	snakview.value( newValue );

	assert.deepEqual(
		snakview.value(),
		newValue,
		'Set Snak serialization value.'
	);

	assert.ok(
		snakview.snak().equals( snakDeserializer.deserialize( newValue ) ),
		'Verified Snak object returned by snak().'
	);

	newValue = new wb.datamodel.PropertyNoValueSnak( 'P1' );

	snakview.value( newValue );

	assert.deepEqual(
		snakview.value(),
		snakSerializer.serialize( newValue ),
		'Set wikibase.datamodel.Snak value.'
	);

	assert.ok(
		snakview.snak().equals( newValue ),
		'Verified Snak object returned by snak().'
	);

	newValue = {
		snaktype: wb.datamodel.PropertyValueSnak.TYPE
	};

	snakview.value( newValue );

	assert.deepEqual(
		snakview.value(),
		newValue,
		'Set incomplete Snak serialization value.'
	);

	assert.strictEqual(
		snakview.snak(),
		null,
		'Verified snak() returning "null".'
	);
} );

QUnit.test( 'snak()', function( assert ) {
	assert.expect( 5 );
	var $snakview = createSnakview(),
		snakview = $snakview.data( 'snakview' );

	assert.strictEqual(
		snakview.snak(),
		null,
		'Returning "null" since default value is an incomplete serialization.'
	);

	var snak = new wb.datamodel.PropertySomeValueSnak( 'P1' );

	snakview.snak( snak );

	assert.ok(
		snakview.snak().equals( snak ),
		'Set Snak value.'
	);

	assert.deepEqual(
		snakview.value(),
		snakSerializer.serialize( snak ),
		'Verified serialization returned by value().'
	);

	snakview.snak( null );

	assert.strictEqual(
		snakview.snak(),
		null,
		'Reset value by passing "null" to snak().'
	);

	assert.deepEqual(
		snakview.value(),
		{},
		'Verified serialization returned by value().'
	);
} );

QUnit.test( 'propertyId()', function( assert ) {
	assert.expect( 5 );
	var $snakview = createSnakview(),
		snakview = $snakview.data( 'snakview' );

	assert.strictEqual(
		snakview.propertyId(),
		null,
		'By default, the Property ID is "null".'
	);

	snakview.propertyId( 'P1' );

	assert.equal(
		snakview.propertyId(),
		'P1',
		'Set Property ID.'
	);

	snakview.propertyId( null );

	assert.strictEqual(
		snakview.propertyId(),
		null,
		'Reset Property ID.'
	);

	snakview.snak( new wb.datamodel.PropertyNoValueSnak( 'P1' ) );

	assert.equal(
		snakview.propertyId(),
		'P1',
		'Property ID is updated when setting a Snak.'
	);

	snakview.propertyId( 'P2' );

	assert.ok(
		snakview.snak().equals( new wb.datamodel.PropertyNoValueSnak( 'P2' ) ),
		'Updated Property ID of Snak.'
	);
} );

QUnit.test( 'snakType()', function( assert ) {
	assert.expect( 5 );
	var $snakview = createSnakview(),
		snakview = $snakview.data( 'snakview' );

	assert.strictEqual(
		snakview.snakType(),
		'value',
		'By default, the Snak type is "value".'
	);

	snakview.snakType( 'novalue' );

	assert.equal(
		snakview.snakType(),
		'novalue',
		'Set Snak type.'
	);

	snakview.snakType( null );

	assert.strictEqual(
		snakview.snakType(),
		null,
		'Reset Snak type.'
	);

	snakview.snak( new wb.datamodel.PropertySomeValueSnak( 'P1' ) );

	assert.equal(
		snakview.snakType(),
		'somevalue',
		'Snak type is updated when setting a Snak.'
	);

	snakview.snakType( 'novalue' );

	assert.ok(
		snakview.snak().equals( new wb.datamodel.PropertyNoValueSnak( 'P1' ) ),
		'Updated Snak type of Snak.'
	);
} );

}( jQuery, QUnit, wikibase, dataTypes, mediaWiki ) );

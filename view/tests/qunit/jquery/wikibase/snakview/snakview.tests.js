/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( wb ) {
	'use strict';

	var datamodel = require( 'wikibase.datamodel' ),
		serialization = require( 'wikibase.serialization' );

	QUnit.module( 'jquery.wikibase.snakview', QUnit.newMwEnvironment( {
		afterEach: function () {
			$( '.test_snakview' ).each( function () {
				var $snakview = $( this ),
					snakview = $snakview.data( 'snakview' );

				if ( snakview ) {
					snakview.destroy();
				}

				$snakview.remove();
			} );
		}
	} ) );

	var snakSerializer = new serialization.SnakSerializer(),
		snakDeserializer = new serialization.SnakDeserializer();

	/**
	 * @param {Object} [options={}]
	 * @param {jQuery} [$node]
	 * @return {jQuery}
	 */
	var createSnakview = function ( options, $node ) {
		options = $.extend( {
			autoStartEditing: false,
			entityIdHtmlFormatter: {
				format: function () {
					return $.Deferred().resolve( 'Label' ).promise();
				}
			},
			entityIdPlainFormatter: {
				format: function ( entityId ) {
					return $.Deferred().resolve( entityId ).promise();
				}
			},
			propertyDataTypeStore: {
				getDataTypeForProperty: function ( entityId ) {
					return $.Deferred().resolve().promise();
				}
			},
			getSnakRemover: function () {
			},
			valueViewBuilder: 'I am a ValueViewBuilder',
			dataTypeStore: wb.dataTypeStore
		}, options || {} );

		$node = $node || $( '<div>' ).appendTo( document.body );

		return $node
			.addClass( 'test_snakview' )
			.snakview( options );
	};

	QUnit.test( 'Create & destroy', function ( assert ) {
		var $snakview = createSnakview(),
			snakview = $snakview.data( 'snakview' );

		assert.true(
			snakview instanceof $.wikibase.snakview,
			'Created widget.'
		);

		snakview.destroy();

		assert.strictEqual(
			$snakview.data( 'snakview' ),
			undefined,
			'Destroyed widget.'
		);

		$snakview = createSnakview( {
			value: new datamodel.PropertyNoValueSnak( 'P1' )
		} );
		snakview = $snakview.data( 'snakview' );

		assert.notStrictEqual(
			snakview,
			undefined,
			'Created widget passing a datamodel.Snak object.'
		);

		snakview.destroy();

		assert.strictEqual(
			$snakview.data( 'snakview' ),
			undefined,
			'Destroyed widget.'
		);

		$snakview = createSnakview( {
			value: snakSerializer.serialize( new datamodel.PropertyNoValueSnak( 'P1' ) )
		} );
		snakview = $snakview.data( 'snakview' );

		assert.notStrictEqual(
			snakview,
			undefined,
			'Created widget passing a Snak serialization.'
		);

		snakview.destroy();

		assert.strictEqual(
			$snakview.data( 'snakview' ),
			undefined,
			'Destroyed widget.'
		);
	} );

	QUnit.test( 'value()', function ( assert ) {
		var $snakview = createSnakview(),
			snakview = $snakview.data( 'snakview' );

		assert.deepEqual(
			snakview.value(),
			{
				snaktype: datamodel.PropertyValueSnak.TYPE
			},
			'Verified default value.'
		);

		var newValue = {
			property: 'P1',
			snaktype: datamodel.PropertySomeValueSnak.TYPE
		};

		snakview.value( newValue );

		assert.deepEqual(
			snakview.value(),
			newValue,
			'Set Snak serialization value.'
		);

		assert.true(
			snakview.snak().equals( snakDeserializer.deserialize( newValue ) ),
			'Verified Snak object returned by snak().'
		);

		newValue = new datamodel.PropertyNoValueSnak( 'P1' );

		snakview.value( newValue );

		assert.deepEqual(
			snakview.value(),
			snakSerializer.serialize( newValue ),
			'Set datamodel.Snak value.'
		);

		assert.true(
			snakview.snak().equals( newValue ),
			'Verified Snak object returned by snak().'
		);

		newValue = {
			snaktype: datamodel.PropertyValueSnak.TYPE
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

	QUnit.test( 'snak()', function ( assert ) {
		var $snakview = createSnakview(),
			snakview = $snakview.data( 'snakview' );

		assert.strictEqual(
			snakview.snak(),
			null,
			'Returning "null" since default value is an incomplete serialization.'
		);

		var snak = new datamodel.PropertySomeValueSnak( 'P1' );

		snakview.snak( snak );

		assert.true(
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

	QUnit.test( 'propertyId()', function ( assert ) {
		var $snakview = createSnakview(),
			snakview = $snakview.data( 'snakview' );

		assert.strictEqual(
			snakview.propertyId(),
			null,
			'By default, the Property ID is "null".'
		);

		snakview.propertyId( 'P1' );

		assert.strictEqual(
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

		snakview.snak( new datamodel.PropertyNoValueSnak( 'P1' ) );

		assert.strictEqual(
			snakview.propertyId(),
			'P1',
			'Property ID is updated when setting a Snak.'
		);

		snakview.propertyId( 'P2' );

		assert.true(
			snakview.snak().equals( new datamodel.PropertyNoValueSnak( 'P2' ) ),
			'Updated Property ID of Snak.'
		);
	} );

	QUnit.test( 'snakType()', function ( assert ) {
		var $snakview = createSnakview(),
			snakview = $snakview.data( 'snakview' );

		assert.strictEqual(
			snakview.snakType(),
			'value',
			'By default, the Snak type is "value".'
		);

		snakview.snakType( 'novalue' );

		assert.strictEqual(
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

		snakview.snak( new datamodel.PropertySomeValueSnak( 'P1' ) );

		assert.strictEqual(
			snakview.snakType(),
			'somevalue',
			'Snak type is updated when setting a Snak.'
		);

		snakview.snakType( 'novalue' );

		assert.true(
			snakview.snak().equals( new datamodel.PropertyNoValueSnak( 'P1' ) ),
			'Updated Snak type of Snak.'
		);
	} );

}( wikibase ) );

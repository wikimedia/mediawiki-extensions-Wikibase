( function ( sinon ) {
	'use strict';

	var PropertyDataTypeStore = require( '../../resources/wikibase.PropertyDataTypeStore.js' );

	QUnit.module( 'wikibase.PropertyDataTypeStore' );

	function newPropertyDataTypeStore( services ) {
		services = services || {};

		return new PropertyDataTypeStore(
			services.entityLoadedHook || { add: sinon.stub().yields( { claims: {} } ) },
			services.entityStore || sinon.stub()
		);
	}

	QUnit.test( 'set/getDataTypeForProperty', function ( assert ) {
		var done = assert.async(),
			dataTypeStore = newPropertyDataTypeStore();

		dataTypeStore.setDataTypeForProperty( 'P123', 'string' );

		dataTypeStore.getDataTypeForProperty( 'P123' ).done( function ( dataType ) {
			assert.strictEqual( dataType, 'string' );
			done();
		} );
	} );

	QUnit.test( 'Given data type not set, checks existing statements for the requested property', function ( assert ) {
		var done = assert.async(),
			expectedType = 'time',
			entityJson = {
				// id, labels, etc not relevant for these tests
				claims: {
					P123: [ {
						mainsnak: { datatype: expectedType }
					} ]
				}
			},
			entityLoadedHook = { add: sinon.stub().yields( entityJson ) },
			dataTypeStore = newPropertyDataTypeStore( { entityLoadedHook: entityLoadedHook } );

		dataTypeStore.getDataTypeForProperty( 'P123' ).done( function ( dataType ) {
			assert.strictEqual( dataType, expectedType );
			done();
		} );
	} );

	QUnit.test( 'Given data type not set and no existing statement for the property, uses entity store', function ( assert ) {
		var done = assert.async(),
			expectedType = 'quantity',
			propertyId = 'P666',
			entityStore = {
				get: sinon.stub().returns( $.Deferred().resolve( {
					getDataTypeId: function () {
						return expectedType;
					}
				} ) )
			},
			dataTypeStore = newPropertyDataTypeStore( { entityStore: entityStore } );

		dataTypeStore.getDataTypeForProperty( propertyId ).done( function ( dataType ) {
			assert.strictEqual( dataType, expectedType );
			sinon.assert.calledWith( entityStore.get, propertyId );

			done();
		} );
	} );

	QUnit.test( 'Given data type cannot be found, returns null', function ( assert ) {
		var done = assert.async(),
			entityStore = {
				get: sinon.stub().returns( $.Deferred().resolve( null ) )
			},
			dataTypeStore = newPropertyDataTypeStore( { entityStore: entityStore } );

		dataTypeStore.getDataTypeForProperty( 'P777' ).done( function ( dataType ) {
			assert.strictEqual( dataType, null );
			done();
		} );
	} );

}( sinon ) );

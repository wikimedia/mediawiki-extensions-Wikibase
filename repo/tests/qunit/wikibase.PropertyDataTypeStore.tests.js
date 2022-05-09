( function ( sinon ) {
	'use strict';

	var PropertyDataTypeStore = require( '../../resources/wikibase.PropertyDataTypeStore.js' );

	QUnit.module( 'wikibase.PropertyDataTypeStore' );

	function newPropertyDataTypeStore( services ) {
		services = services || {};

		return new PropertyDataTypeStore(
			services.entityLoadedHook || { add: sinon.stub().yields( { claims: {} } ) },
			services.entityStore || { get: sinon.stub().returns( $.Deferred().resolve( null ) ) }
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

	QUnit.test( 'Checks existing statements for the requested property\'s data type', function ( assert ) {
		var testData = [
				{
					message: 'getting the data type from a main snak',
					expected: 'time',
					property: 'P123',
					entity: {
						// id, labels, etc not relevant for these tests
						claims: {
							P123: [ {
								mainsnak: { datatype: 'time' }
							} ]
						}
					}
				},
				{
					message: 'getting the data type from a qualifier',
					expected: 'string',
					property: 'P666',
					entity: {
						claims: {
							P123: [ {
								qualifiers: {
									P666: [
										{
											datatype: 'string'
										}
									]
								}
							} ]
						}
					}
				},
				{
					// This is a contrived example because at the time of writing the lexeme json is missing the data
					// type on statements on sub entities for some reason -> T249206.
					message: 'getting the data type from a statement on a sub entity',
					expected: 'quantity',
					property: 'P789',
					entity: {
						senses: [ {
							claims: {
								P789: [ {
									mainsnak: { datatype: 'quantity' }
								} ]
							}
						} ]
					}
				},
				{
					message: 'returns null if a statement exists for the property but without data type',
					expected: null,
					property: 'P321',
					entity: {
						senses: [ {
							claims: {
								P321: [ {
									mainsnak: {
										// datatype: 'missing :('
									}
								} ]
							}
						} ]
					}
				}
			],
			done = assert.async( testData.length );

		testData.forEach( function ( test ) {
			var entityLoadedHook = { add: sinon.stub().yields( test.entity ) },
				dataTypeStore = newPropertyDataTypeStore( { entityLoadedHook: entityLoadedHook } );

			dataTypeStore.getDataTypeForProperty( test.property ).done( function ( dataType ) {
				assert.strictEqual( dataType, test.expected, test.message );
				done();
			} );
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

	QUnit.test( 'Given previous successful entity JSON lookup, it remembers the result', function ( assert ) {
		var done = assert.async(),
			expectedType = 'string',
			propertyId = 'P123',
			entity = {
				claims: {
					P123: [ {
						mainsnak: { datatype: expectedType }
					} ]
				}
			},
			entityLoadedHook = { add: sinon.stub().yields( entity ) },
			dataTypeStore = newPropertyDataTypeStore( { entityLoadedHook: entityLoadedHook } );

		dataTypeStore.getDataTypeForProperty( propertyId ).then( function ( dataType ) {
			assert.strictEqual( dataType, expectedType );

			dataTypeStore.getDataTypeForProperty( propertyId ).then( function ( dataType2 ) {
				assert.strictEqual( dataType2, expectedType );
				sinon.assert.calledOnce( entityLoadedHook.add );

				done();
			} );
		} );
	} );

	QUnit.test( 'Given previous successful entity entityStore lookup, it remembers the result', function ( assert ) {
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

		dataTypeStore.getDataTypeForProperty( propertyId ).then( function ( dataType ) {
			assert.strictEqual( dataType, expectedType );

			dataTypeStore.getDataTypeForProperty( propertyId ).then( function ( dataType2 ) {
				assert.strictEqual( dataType2, expectedType );
				sinon.assert.calledOnce( entityStore.get );

				done();
			} );
		} );
	} );

}( sinon ) );

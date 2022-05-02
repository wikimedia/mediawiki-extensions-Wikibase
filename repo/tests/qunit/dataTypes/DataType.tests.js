/**
 * @license GPL-2.0-or-later
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */
( function ( QUnit ) {
	'use strict';

	var DataType = require( '../../../resources/dataTypes/DataType.js' );

	QUnit.module( 'wikibase.dataTypes.DataType' );

	QUnit.test( 'constructor', function ( assert ) {
		var dataType = new DataType( 'foo', 'string' );

		assert.true(
			dataType instanceof DataType,
			'New data type created and instance of DataType'
		);
	} );

	QUnit.test( 'getId', function ( assert ) {
		var dataType = new DataType( 'foo', 'string' );

		assert.strictEqual(
			dataType.getId(),
			'foo',
			'getId() returns string ID provided in constructor'
		);
	} );

	QUnit.test( 'getDataValueType', function ( assert ) {
		var dataType = new DataType( 'foo', 'string' ),
			dvType = dataType.getDataValueType();

		assert.strictEqual(
			typeof dvType,
			'string',
			'getDataValueType() returns string'
		);

		assert.notStrictEqual(
			dvType,
			'',
			'string returned by getDataValueType() is not empty'
		);
	} );

	var invalidArguments = [
		{
			title: 'no arguments',
			constructorParams: []
		},
		{
			title: 'missing data value type',
			constructorParams: [ 'foo' ]
		},
		{
			title: 'wrong type for data value type',
			constructorParams: [ 'foo', {} ]
		},
		{
			title: 'wrong type for ID',
			constructorParams: [ null, 'xxx' ]
		}
	];

	QUnit.test( 'invalid constructor arguments', function ( assert ) {
		function instantiateObject( testArguments ) {
			return function () {
				var args = testArguments.constructorParams;
				return new DataType( args[ 0 ], args[ 1 ] );
			};
		}

		for ( var i = 0; i < invalidArguments.length; i++ ) {
			assert.throws(
				instantiateObject( invalidArguments[ i ] ),
				'DataType can not be constructed from invalid arguments: ' + invalidArguments[ i ].title
			);
		}
	} );

}( QUnit ) );

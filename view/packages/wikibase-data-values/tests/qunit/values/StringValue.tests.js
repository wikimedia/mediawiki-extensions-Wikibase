/**
 * QUnit tests for inherit() function for more prototypal inheritance convenience.
 *
 * @since 0.1
 * @file
 * @ingroup DataValues
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
( function( dv, $, QUnit, undefined ) {
	'use strict';

	var PARENT = dv.tests.DataValueTest,
		constructor = function() {
		};

	/**
	 * Constructor for creating a test object for the string DataValue.
	 *
	 * @constructor
	 * @extends dv.tests.DataValueTest
	 * @since 0.1
	 */
	dv.tests.StringValueTest = dv.util.inherit( PARENT, constructor, {

		/**
		 * @see dv.tests.DataValueTest.getObject
		 */
		getObject: function() {
			return dv.StringValue;
		},

		/**
		 * @see dv.tests.DataValueTest.getConstructorArguments
		 */
		getConstructorArguments: function() {
			return [
				[ '' ],
				[ 'foo' ],
				[ ' foo bar baz foo bar baz. foo bar baz ' ]
			];
		}

	} );

	var test = new dv.tests.StringValueTest();

	test.runTests( 'dataValues.StringValue' );

	// TODO
//	QUnit.test( 'constructor, getValue, getSortKey, getJSON', function() {
//		var values = [
//			'',
//			'foo',
//			'foo bar baz'
//		];
//
//		for ( var value in values ) {
//			var instance = new dv.StringValue( value );
//			QUnit.strictEqual( instance.getValue(), value );
//			QUnit.strictEqual( instance.getSortKey(), value );
//			QUnit.strictEqual( instance.toJSON(), value );
//		}
//	} );

}( dataValues, jQuery, QUnit ) );

/**
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
	 * Constructor for creating a test object for the MonolingualTextValue.
	 *
	 * @constructor
	 * @extends dv.tests.DataValueTest
	 * @since 0.1
	 */
	dv.tests.MonolingualTextValueTest = dv.util.inherit( PARENT, constructor, {

		/**
		 * @see dv.tests.DataValueTest.getObject
		 */
		getObject: function() {
			return dv.MonolingualTextValue;
		},

		/**
		 * @see dv.tests.DataValueTest.getConstructorArguments
		 */
		getConstructorArguments: function() {
			return [
				[ 'en', '' ],
				[ 'de', 'foo' ],
				[ 'nl', ' foo bar baz foo bar baz. foo bar baz ' ]
			];
		},

		/**
		 * Tests the getText function.
		 *
		 * @param {QUnit} assert
		 */
		testGetText: function( assert ) {
			var
				constructorArgs = this.getConstructorArguments(),
				i,
				instance;

			for ( i in constructorArgs ) {
				instance = this.getInstance( constructorArgs[i] );

				assert.strictEqual(
					instance.getText(),
					constructorArgs[i][1],
					'getText must return the value that was provided as second argument in the constructor'
				);
			}
		},

		/**
		 * Tests the getText function.
		 *
		 * @param {QUnit} assert
		 */
		testGetLanguageCode: function( assert ) {
			var
				constructorArgs = this.getConstructorArguments(),
				i,
				instance;

			for ( i in constructorArgs ) {
				instance = this.getInstance( constructorArgs[i] );

				assert.strictEqual(
					instance.getLanguageCode(),
					constructorArgs[i][0],
					'getLanguageCode must return the value that was provided as first argument in the constructor'
				);
			}
		}

	} );

	var test = new dv.tests.MonolingualTextValueTest();

	test.runTests( 'dataValues.MonolingualTextValue' );

}( dataValues, jQuery, QUnit ) );

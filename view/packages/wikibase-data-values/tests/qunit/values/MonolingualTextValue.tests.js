/**
 * @since 0.1
 * @file
 * @ingroup DataValues
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
( function( dv, $, QUnit ) {
	'use strict';

	var PARENT = dv.tests.DataValueTest;

	/**
	 * Constructor for creating a test object for the MonolingualTextValue.
	 *
	 * @constructor
	 * @extends dv.tests.DataValueTest
	 * @since 0.1
	 */
	dv.tests.MonolingualTextValueTest = dv.util.inherit( PARENT, {

		/**
		 * @see dv.tests.DataValueTest.getConstructor
		 */
		getConstructor: function() {
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
		testGetText: PARENT.createGetterTest( 1, 'getText' ),

		/**
		 * Tests the getText function.
		 *
		 * @param {QUnit} assert
		 */
		testGetLanguageCode: PARENT.createGetterTest( 0, 'getLanguageCode' )

	} );

	var test = new dv.tests.MonolingualTextValueTest();

	test.runTests( 'dataValues.MonolingualTextValue' );

}( dataValues, jQuery, QUnit ) );

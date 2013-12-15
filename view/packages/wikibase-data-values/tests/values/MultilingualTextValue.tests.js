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
	 * Constructor for creating a test object for the MultilingualTextValue.
	 *
	 * @constructor
	 * @extends dv.tests.DataValueTest
	 * @since 0.1
	 */
	dv.tests.MultilingualTextValueTest = dv.util.inherit( PARENT, {

		/**
		 * @see dv.tests.DataValueTest.getConstructor
		 */
		getConstructor: function() {
			return dv.MultilingualTextValue;
		},

		/**
		 * @see dv.tests.DataValueTest.getConstructorArguments
		 */
		getConstructorArguments: function() {
			return [
				[ [ new dv.MonolingualTextValue( 'en', '' ) ] ],
				[ [ new dv.MonolingualTextValue( 'de', 'foo' ) ] ],
				[ [ new dv.MonolingualTextValue( 'nl', ' foo bar baz foo bar baz. foo bar baz ' ) ] ],
				[ [
					new dv.MonolingualTextValue( 'en', '' ),
					new dv.MonolingualTextValue( 'de', 'foo' ),
					new dv.MonolingualTextValue( 'nl', ' foo bar baz foo bar baz. foo bar baz ' )
				] ]
			];
		},

		/**
		 * Tests the getTexts function.
		 *
		 * @param {QUnit} assert
		 */
		testGetTexts: PARENT.createGetterTest( 0, 'getTexts' )

	} );

	var test = new dv.tests.MultilingualTextValueTest();

	test.runTests( 'dataValues.MultilingualTextValue' );

}( dataValues, jQuery, QUnit ) );

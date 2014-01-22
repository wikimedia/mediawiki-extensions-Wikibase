/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
( function( dv, util ) {
	'use strict';

	var PARENT = dv.tests.DataValueTest;

	/**
	 * Constructor for creating a test object for the MonolingualTextValue.
	 *
	 * @constructor
	 * @extends dv.tests.DataValueTest
	 * @since 0.1
	 */
	dv.tests.MonolingualTextValueTest = util.inherit( PARENT, {

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
		 * @see dataValues.tests.DataValueTest.createGetterTest
		 */
		testGetText: PARENT.createGetterTest( 1, 'getText' ),

		/**
		 * @see dataValues.tests.DataValuesTest.createGetterTest
		 */
		testGetLanguageCode: PARENT.createGetterTest( 0, 'getLanguageCode' )

	} );

	var test = new dv.tests.MonolingualTextValueTest();

	test.runTests( 'dataValues.MonolingualTextValue' );

}( dataValues, util ) );

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
define( [
	'dataValues/dataValues',
	'util/util.inherit',
	'tests/src/dataValues.DataValue.tests',
	'values/MultilingualTextValue',
	'values/MonolingualTextValue'
], function( dv, util ) {
	'use strict';

	var PARENT = dv.tests.DataValueTest;

	/**
	 * Constructor for creating a test object for the MultilingualTextValue.
	 *
	 * @constructor
	 * @extends dv.tests.DataValueTest
	 * @since 0.1
	 */
	dv.tests.MultilingualTextValueTest = util.inherit( PARENT, {

		/**
		 * @inheritdoc
		 */
		getConstructor: function() {
			return dv.MultilingualTextValue;
		},

		/**
		 * @inheritdoc
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
		 * @see dataValues.tests.DataValuesTest.createGetterTest
		 */
		testGetTexts: PARENT.createGetterTest( 0, 'getTexts' )

	} );

	var test = new dv.tests.MultilingualTextValueTest();

	test.runTests( 'dataValues.MultilingualTextValue' );

} );

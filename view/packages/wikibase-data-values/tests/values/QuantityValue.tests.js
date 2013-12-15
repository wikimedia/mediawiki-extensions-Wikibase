/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( dv, $, QUnit ) {
	'use strict';

	var PARENT = dv.tests.DataValueTest;

	/**
	 * Constructor for creating a test object for the QuantityValue DataValue.
	 *
	 * @constructor
	 * @extends dv.tests.DataValueTest
	 * @since 0.1
	 */
	dv.tests.QuantityValueTest = dv.util.inherit( PARENT, {

		/**
		 * @see dv.tests.DataValueTest.getConstructor
		 */
		getConstructor: function() {
			return dv.QuantityValue;
		},

		/**
		 * @see dv.tests.DataValueTest.getConstructorArguments
		 */
		getConstructorArguments: function() {
			return [
				[new dv.DecimalValue( 0 ), 'some unit', new dv.DecimalValue( 0 ), new dv.DecimalValue( 0 )],
				[new dv.DecimalValue( 0 ), 'some unit', new dv.DecimalValue( -1 ), new dv.DecimalValue( 1 )],
				[new dv.DecimalValue( 5 ), 'some unit', new dv.DecimalValue( 4 ), new dv.DecimalValue( 6 )]
			];
		}

	} );

	var test = new dv.tests.QuantityValueTest();

	test.runTests( 'dataValues.QuantityValueTest' );

}( dataValues, jQuery, QUnit ) );

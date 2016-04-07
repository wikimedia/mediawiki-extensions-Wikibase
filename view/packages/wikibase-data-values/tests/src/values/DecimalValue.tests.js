/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
define( [
	'dataValues/dataValues',
	'util/util.inherit',
	'tests/src/dataValues.DataValue.tests',
	'values/DecimalValue'
], function( dv, util ) {
	'use strict';

	var PARENT = dv.tests.DataValueTest;

	/**
	 * Constructor for creating a test object for the DecimalValue DataValue.
	 *
	 * @constructor
	 * @extends dv.tests.DataValueTest
	 * @since 0.1
	 */
	dv.tests.DecimalValueTest = util.inherit( PARENT, {

		/**
		 * @inheritdoc
		 */
		getConstructor: function() {
			return dv.DecimalValue;
		},

		/**
		 * @inheritdoc
		 */
		getConstructorArguments: function() {
			return [
				[0],
				[1],
				[1e30],
				[1.5e30],
				[1.5e-30],
				[-1],
				[-1.5e-30],
				['+0'],
				['+1'],
				['-0'],
				['-1'],
				['+100000000000000000'],
				['-100000000000000000'],
				['-0.1'],
				['+0.1']
			];
		}

	} );

	var test = new dv.tests.DecimalValueTest();

	test.runTests( 'dataValues.DecimalValueTest' );

} );

/**
 * @license GPL-2.0+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( dv, util, $ ) {
	'use strict';

	var PARENT = dv.tests.DataValueTest;

	/**
	 * Constructor for creating a test object for the ununserializable DataValue.
	 *
	 * @constructor
	 * @extends dv.tests.DataValueTest
	 * @since 0.1
	 */
	dv.tests.UnDeserializableValueTest = util.inherit( PARENT, {

		/**
		 * @inheritdoc
		 */
		getConstructor: function() {
			return dv.UnDeserializableValue;
		},

		/**
		 * @inheritdoc
		 */
		getConstructorArguments: function() {
			return [
				[ {}, 'sometype', 'some error' ],
				[ { foo: 'bar' }, 'another-type', 'another error' ]
			];
		},

		/**
		 * Tests the getStructure method.
		 *
		 * @since 0.1
		 *
		 * @param {QUnit.assert} assert
		 */
		testGetStructure: function( assert ) {
			var instances = this.getInstances(),
				i,
				structure;

			for ( i in instances ) {
				structure = instances[i].getStructure();

				assert.ok(
					$.isPlainObject( structure ),
					'return value is plain object'
				);

				assert.ok(
					structure !== instances[i].getStructure(),
					'return value not returned by reference'
				);
			}
		},

		/**
		 * @see dv.tests.DataValueTest.testJsonRoundtripping
		 *
		 * skip
		 * TODO: activate after equals is implemented according to TODO in the data value's file
		 */
		testEquals: null
	} );

	var test = new dv.tests.UnDeserializableValueTest();

	test.runTests( 'dataValues.UnDeserializableValue' );

}( dataValues, util, jQuery ) );

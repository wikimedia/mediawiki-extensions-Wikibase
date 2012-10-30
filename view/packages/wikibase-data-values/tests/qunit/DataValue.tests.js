/**
 * @file
 * @ingroup DataValues
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
( function( dv, $, QUnit, undefined ) {
	'use strict';

	dv.tests = {};

	/**
	 * Base constructor for DataValue object tests.
	 *
	 * @constructor
	 * @abstract
	 * @since 0.1
	 */
	dv.tests.DataValueTest = function() {};
	dv.tests.DataValueTest.prototype = {

		/**
		 * Data provider that provides valid constructor arguments.
		 *
		 * @since 0.1
		 *
		 * @return array
		 */
		getConstructorArguments: dv.util.abstractMember,

		/**
		 * Returns the dataValue object to be tested (ie dv.StringValue).
		 *
		 * @since 0.1
		 *
		 * @return dv.DataValue
		 */
		getObject: dv.util.abstractMember,

		/**
		 * Returns the dataValue object to be tested (ie dv.StringValue).
		 *
		 * @since 0.1
		 *
		 * @return dv.DataValue
		 */
		getInstance: function( constructorArguments ) {
			var
				self = this,
				DataValueInstance = function( constructorArguments ) {
					self.getObject().apply( this, constructorArguments );
				};

			DataValueInstance.prototype = this.getObject().prototype;

			return new DataValueInstance( constructorArguments );
		},

		/**
		 * Returns the dataValue object to be tested (ie dv.StringValue).
		 *
		 * @since 0.1
		 *
		 * @return dv.DataValue
		 */
		getInstances: function() {
			var self = this;

			return this.getConstructorArguments().map( function( constructorArguments ) {
				return self.getInstance( constructorArguments );
			} );
		},

		/**
		 * Runs the tests.
		 *
		 * @since 0.1
		 *
		 * @param {String} moduleName
		 */
		runTests: function( moduleName ) {
			QUnit.module( moduleName, QUnit.newMwEnvironment() );

			var self = this;

			QUnit.test( 'testConstructor', function( assert ) { self.testConstructor( assert ); } );
			QUnit.test( 'testGetSortKey', function( assert ) { self.testGetSortKey( assert ); } );
		},

		/**
		 * Tests the constructor.
		 *
		 * @since 0.1
		 *
		 * @param {QUnit} assert
		 */
		testConstructor: function( assert ) {
			var
				self = this,
				constructorArgs = this.getConstructorArguments(),
				i,
				instance;

			for ( i in constructorArgs ) {
				instance = self.getInstance( constructorArgs[i] );

				assert.equal(
					typeof( instance.getType() ),
					'string',
					'getType method is present and returns string'
				);
			}
		},

		/**
		 * Tests the getSortKey method.
		 *
		 * @since 0.1
		 *
		 * @param {QUnit} assert
		 */
		testGetSortKey: function( assert ) {
			var
				instances = this.getInstances(),
				i,
				keyType;

			for ( i in instances ) {
				keyType = typeof( instances[i].getSortKey() );

				assert.ok(
					keyType === 'string' || keyType === 'number',
					'return value is a string or number'
				);
			}
		}

	};

}( dataValues, jQuery, QUnit ) );

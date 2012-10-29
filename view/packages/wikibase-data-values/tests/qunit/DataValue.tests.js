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

			return this.getConstructorArguments.map( function( constructorArguments ) {
				return self.getInstance( constructorArguments );
			} );
		},

		/**
		 * Runs the tests.
		 *
		 * @since 0.1
		 */
		runTests: function( moduleName ) {
			QUnit.module( moduleName, QUnit.newMwEnvironment() );

			this.testConstructor();
		},

		/**
		 * Tests the constructor.
		 *
		 * @since 0.1
		 */
		testConstructor: function() {
			var
				self = this,
				constructorArgs = this.getConstructorArguments(),
				i;

			for ( i in constructorArgs ) {
				QUnit.test( 'constructor', function( assert ) {
					var instance = self.getInstance( constructorArgs[i] );
					assert.equal( typeof( instance.getType() ), 'string' );
				} );
			}
		}

	};

}( dataValues, jQuery, QUnit ) );

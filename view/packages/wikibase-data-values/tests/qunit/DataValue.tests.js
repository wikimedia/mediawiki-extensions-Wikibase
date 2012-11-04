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
		 * @param {Array} constructorArguments
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

			$.each( this, function( property, value ) {
				if ( property.substring( 0, 4 ) === 'test' && $.isFunction( self[property] ) ) {
					QUnit.test(
						property,
						function( assert ) {
							self[property].call( self, assert );
						}
					);
				}
			} );
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
				constructorArgs = this.getConstructorArguments(),
				i,
				instance;

			for ( i in constructorArgs ) {
				instance = this.getInstance( constructorArgs[i] );

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
		},

		/**
		 * Tests the toJSON method.
		 *
		 * @since 0.1
		 *
		 * @param {QUnit} assert
		 */
		testToJSON: function( assert ) {
			var
				instances = this.getInstances(),
				i,
				jsonValue;

			for ( i in instances ) {
				jsonValue = instances[i].toJSON();
				assert.ok( true ); // TODO: add meaningful assertion
			}
		},

		/**
		 * Tests the getValue method.
		 *
		 * @since 0.1
		 *
		 * @param {QUnit} assert
		 */
		testGetValue: function( assert ) {
			var
				instances = this.getInstances(),
				i,
				value;

			for ( i in instances ) {
				value = instances[i].getValue();
				assert.ok( true ); // TODO: add meaningful assertion
			}
		},

		/**
		 * Tests the equals method.
		 *
		 * @since 0.1
		 *
		 * @param {QUnit} assert
		 */
		testEquals: function( assert ) {
			var
				instances = this.getInstances(),
				i;

			for ( i in instances ) {
				assert.ok(
					instances[i].equals( instances[i] ),
					'instance is equal to itself'
				);

				assert.ok(
					!instances[i].equals( 42 ),
					'instance is not equal to 42'
				);
			}
		}

	};

	/**
	 * Creates and returns a test method for a getter.
	 * Only works for simpler getters, ie those that return one of the arguments provided to the constructor.
	 *
	 * @since 0.1
	 *
	 * @param {Number} argNumber
	 * @param {String} functionName
	 *
	 * @return {Function}
	 */
	dv.tests.DataValueTest.createGetterTest = function( argNumber, functionName ) {
		return function() {
			var
				constructorArgs = this.getConstructorArguments(),
				i,
				instance;

			for ( i in constructorArgs ) {
				instance = this.getInstance( constructorArgs[i] );

				assert.strictEqual(
					instance[functionName].call( instance ),
					constructorArgs[i][argNumber],
					functionName + ' must return the value that was provided as argument ' + argNumber + ' in the constructor'
				);
			}
		};
	};

}( dataValues, jQuery, QUnit ) );

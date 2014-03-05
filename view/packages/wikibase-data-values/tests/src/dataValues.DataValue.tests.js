/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
define( [
	'dataValues/dataValues',
	'util/util.inherit',
	'jquery',
	'qunit'
], function( dv, util, $, QUnit ) {
	'use strict';

	dv.tests = {};

	/**
	 * Base constructor for DataValue object tests.
	 *
	 * @constructor
	 * @abstract
	 * @since 0.1
	 */
	var SELF = dv.tests.DataValueTest = function() {};

	$.extend( SELF.prototype, {
		/**
		 * Data provider that provides valid constructor arguments.
		 *
		 * @since 0.1
		 *
		 * @return array
		 */
		getConstructorArguments: util.abstractMember,

		/**
		 * Returns the dataValue constructor to be tested (ie dv.StringValue).
		 *
		 * @since 0.1
		 *
		 * @return Function
		 */
		getConstructor: util.abstractMember,

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
			var self = this,
				OriginalConstructor = self.getConstructor(),
				DataValueConstructor = function( constructorArguments ) {
					OriginalConstructor.apply( this, constructorArguments );
				};

			DataValueConstructor.prototype = OriginalConstructor.prototype;

			return new DataValueConstructor( constructorArguments );
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
			QUnit.module( moduleName );

			var self = this;

			$.each( this, function( property, value ) {
				if ( property.substring( 0, 4 ) === 'test' && $.isFunction( self[property] ) ) {
					QUnit.test(
						property,
						function( assert ) {
							self[property]( assert );
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
			var constructorArgs = this.getConstructorArguments(),
				i,
				instance;

			for ( i in constructorArgs ) {
				instance = this.getInstance( constructorArgs[i] );

				assert.ok(
					instance instanceof dv.DataValue,
					'is instance of DataValue'
				);
				assert.ok(
					instance instanceof this.getConstructor(),
					'is instance of actual data value implementation\'s constructor'
				);
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
		 * @param {QUnit.assert} assert
		 */
		testGetSortKey: function( assert ) {
			var instances = this.getInstances(),
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
		 * Tests whether the data value's constructor has a newFromJSON function.
		 *
		 * @since 0.1
		 *
		 * @param {QUnit.assert} assert
		 */
		testNewFromJSON: function( assert ) {
			var fnNewFromJSON = this.getConstructor().newFromJSON;

			assert.ok(
				$.isFunction( fnNewFromJSON ),
				'has a related newFromJSON function'
			);
		},

		/**
		 * Tests the toJSON method.
		 *
		 * @since 0.1
		 *
		 * @param {QUnit.assert} assert
		 */
		testToJSON: function( assert ) {
			var instances = this.getInstances(),
				i,
				jsonValue;

			for ( i in instances ) {
				jsonValue = instances[i].toJSON();

				assert.ok(
					jsonValue !== undefined,
					'toJSON() returned some value'
				);
			}
		},

		/**
		 * Gets a data values JSON, constructs a new data value from it by using the newFromJSON
		 * and checks whether the two are equal.
		 *
		 * @param {QUnit.assert} assert
		 */
		testJsonRoundtripping: function( assert ) {
			var instances = this.getInstances(),
				fnNewFromJSON = this.getConstructor().newFromJSON,
				i,
				value1,
				value2,
				jsonValue;

			for ( i in instances ) {
				value1 = instances[i];
				jsonValue = value1.toJSON();
				value2 = fnNewFromJSON( jsonValue );

				assert.ok(
					value1.equals( value2 ) && value2.equals( value1 ),
					'data value created from another data values JSON is equal to its donor'
				);
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
			var instances = this.getInstances(),
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
			var instances = this.getInstances(),
				instances2 = this.getInstances(),
				i;

			for ( i in instances ) {
				assert.ok(
					instances[i].equals( instances[i] ),
					'instance is equal to itself'
				);

				if ( instances[i].getType() !== 'unknown' ) {
					assert.ok(
						instances[i] !== instances2[i] && instances[i].equals( instances2[i] ),
						'instances is equal to another instance encapsulating the same value'
					);
				}
			}
		}

	} );

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
	SELF.createGetterTest = function( argNumber, functionName ) {
		return function() {
			var
				constructorArgs = this.getConstructorArguments(),
				i,
				instance;

			for ( i in constructorArgs ) {
				instance = this.getInstance( constructorArgs[i] );

				QUnit.assert.strictEqual(
					instance[functionName].call( instance ),
					constructorArgs[i][argNumber],
					functionName + ' must return the value that was provided as argument ' + argNumber + ' in the constructor'
				);
			}
		};
	};

} );

/**
 * @file
 * @ingroup DataValues
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < danweetz@web.de >
 */
( function( vp, dv, $, QUnit, undefined ) {
	'use strict';

	vp.tests = {};

	/**
	 * Base constructor for ValueParser object tests.
	 *
	 * @constructor
	 * @abstract
	 * @since 0.1
	 */
	vp.tests.ValueParserTest = function() {};
	vp.tests.ValueParserTest.prototype = {

		/**
		 * Data provider that provides valid parse arguments.
		 *
		 * @since 0.1
		 *
		 * @return array
		 */
		getParseArguments: vp.util.abstractMember,

		/**
		 * Returns the ValueParser object to be tested (ie vp.IntParser).
		 *
		 * @since 0.1
		 *
		 * @return vp.ValueParser
		 */
		getObject: vp.util.abstractMember,

		/**
		 * Returns the dataValue object to be tested (ie dv.StringValue).
		 *
		 * @since 0.1
		 *
		 * @param {Array} constructorArguments
		 *
		 * @return vp.ValueParser
		 */
		getInstance: function( constructorArguments ) {
			constructorArguments = constructorArguments || this.getDefaultConstructorArgs();

			var
				self = this,
				ValueParserInstance = function( constructorArguments ) {
					self.getObject().apply( this, constructorArguments );
				};

			ValueParserInstance.prototype = this.getObject().prototype;
			return new ValueParserInstance( constructorArguments );
		},

		getDefaultConstructorArgs: function() {
			return [];
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
		 * Tests the parse method.
		 *
		 * @since 0.1
		 *
		 * @param {QUnit} assert
		 */
		testParse: function( assert ) {
			var parseArguments = this.getParseArguments(),
				parser = this.getInstance(),
				requests = [];

			// prevent from going to next test until all parser requests are handled
			QUnit.stop();

			$.each( parseArguments, function( i, args ) {
				var parseInput = args[0],
					expected = args[1],
					inputDetailMsg = typeof parseInput === 'string'
						? 'for input "' + parseInput + '" '
						: '';

				var request = parser.parse( parseInput )
					.always( function() {
						//QUnit.start();
					} )
					.done( function( dataValue ) {
						// promise resolved, so no error has occured
						assert.ok( true, 'parsing succeeded' );

						assert.ok(
							dataValue === null || ( dataValue instanceof dv.DataValue ),
							'result ' + inputDetailMsg + 'is instanceof DataValue or null'
						);

						if( expected !== undefined ) {
							assert.ok(
								dataValue === expected || dataValue.equals( expected ),
								'result ' + inputDetailMsg + 'is equal to the expected DataValue'
							);
						}
					} )
					.fail( function( errorMessage ) {
						assert.ok( false, 'parsing ' + inputDetailMsg + 'failed: ' + errorMessage );
					} );

				requests.push( request );
			} );

			// only start next test after all parser requests are handled
			$.when.apply( null, requests ).always( function() {
				QUnit.start();
			} );
		}

	};

}( valueParsers, dataValues, jQuery, QUnit ) );

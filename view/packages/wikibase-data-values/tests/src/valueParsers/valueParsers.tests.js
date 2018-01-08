/**
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */
( function( vp, dv, util, $, QUnit ) {
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
		getParseArguments: util.abstractMember,

		/**
		 * Returns the ValueParser constructor to be tested.
		 *
		 * @since 0.1
		 *
		 * @return {Function}
		 */
		getConstructor: util.abstractMember,

		/**
		 * Returns the ValueParser instance to be tested.
		 *
		 * @since 0.1
		 *
		 * @param {Array} constructorArguments
		 *
		 * @return {valueParsers.ValueParser}
		 */
		getInstance: function( constructorArguments ) {
			constructorArguments = constructorArguments || this.getDefaultConstructorArgs();

			var
				self = this,
				ValueParserConstructor = function( constructorArguments ) {
					self.getConstructor().apply( this, constructorArguments );
				};

			ValueParserConstructor.prototype = this.getConstructor().prototype;
			return new ValueParserConstructor( constructorArguments );
		},

		getDefaultConstructorArgs: function() {
			return [];
		},

		/**
		 * Runs the tests.
		 *
		 * @since 0.1
		 *
		 * @param {string} moduleName
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
		 * @param {QUnit.assert} assert
		 */
		testParse: function( assert ) {
			var parseArguments = this.getParseArguments(),
				parser = this.getInstance();

			$.each( parseArguments, function( i, args ) {
				var parseInput = args[0],
					expected = args[1],
					inputDetailMsg = typeof parseInput === 'string'
						? 'for input "' + parseInput + '" '
						: '',
					done = assert.async();

				parser.parse( parseInput )
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
					} )
					.always( done );
			} );
		}

	};

}( valueParsers, dataValues, util, jQuery, QUnit ) );

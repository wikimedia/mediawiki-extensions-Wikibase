/**
 * @since 0.4
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
( function( wb, vp, dv, $, QUnit, undefined ) {
	'use strict';

	var PARENT = vp.tests.ValueParserTest,
		constructor = function() {
		};

	/**
	 * Constructor for creating a test object holding tests for the EntityIdParser.
	 *
	 * @constructor
	 * @extends dv.tests.ValueParserTest
	 * @since 0.1
	 */
	wb.tests.EntityIdParserTest = vp.util.inherit( PARENT, constructor, {

		/**
		 * @see vp.tests.ValueParserTest.getObject
		 */
		getObject: function() {
			return wb.EntityIdParser;
		},

		/**
		 * @see vp.tests.ValueParserTest.getParseArguments
		 */
		getParseArguments: function() {
			// FIXME: right now encapsulation is broken since settings are pulled in server side
			// This can be fixed as soon as the parser gets implemented properly
			var validValues = {
				'q1': ['item', 1],
				'p1': ['property', 1],
				'q42': ['item', 42],
				'p42': ['property', 42]
			};

			var argLists = [];

			// build a list with arrays as entries, [0] is parser input, [1] expected output:
			for ( var rawValue in validValues ) {
				if ( validValues.hasOwnProperty( rawValue ) ) {
					argLists.push( [ rawValue, new wb.EntityId( validValues[rawValue][0], validValues[rawValue][1] ) ] );
				}
			}

			return argLists;
		},

		getDefaultConstructorArgs: function() {
			return [{
				'prefixmap': {
					'q': 'item',
					'p': 'property'
				}
			}];
		}

	} );

	var test = new wb.tests.EntityIdParserTest();

	test.runTests( 'wikibase.parsers.EntityIdParser' );

}( wikibase, valueParsers, dataValues, jQuery, QUnit ) );

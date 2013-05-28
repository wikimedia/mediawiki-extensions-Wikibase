/**
 * @since 0.1
 * @file
 * @ingroup coordinate.js
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( QUnit, $, coordinate ) {
	'use strict';

	/**
	 * Valid coordinate strings:
	 * { <{string} input>: <{number[]} parse result> }
	 * @type {Object}
	 */
	var valid = {
		'0': [0, 0, 1],
		'-3 +2': [-3, 2, 1],
		'1.1 2': [1.1, 2, 0.1],
		'90° N 30.10°': [90, 30.1, 0.01],
		'0° 5\'N, 0° 0\' 10"E': [0.08333333333333333, 0.002777777777777778, 0.0002777777777777778],
		'5\'S': [-5, 0, 1],
		'1\' 1"': [1.0002777777777778, 0, 0.0002777777777777778],
		'1\' 1.1"': [1.0003055555555556, 0, 0.00002777777777777778],
		'190° 30" 1.123\'': [190.5, 1.123, 0.001],
		'5\'N 0\' 10.5"W': [5, -0.002916666666666667, 0.00002777777777777778]
	};

	/**
	 * Invalid coordinate strings.
	 * @type {string[]}
	 */
	var invalid = [
		'random string'
	];

	QUnit.module( 'coordinate.parser.js' );

	QUnit.test( 'Parsing valid coordinate strings', function( assert ) {

		$.each( valid, function( string, expected ) {

			assert.deepEqual(
				coordinate.parser.parse( string ),
				expected,
				'Successfully parsed \'' + string + '\'.'
			);

		} );

	} );

	QUnit.test( 'Parsing invalid coordinate strings', function( assert ) {

		$.each( invalid, function( i, string ) {

			assert.throws(
				function() { coordinate.parser.parse( string ); },
				'Unable to parse \'' + string + '\'.'
			);

		} );

	} );

}( QUnit, jQuery, coordinate ) );

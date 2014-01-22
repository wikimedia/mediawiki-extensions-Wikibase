/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( define ) {
'use strict';

var DEPS = ['globeCoordinate', 'jquery', 'qunit'];

define( DEPS, function( globeCoordinate, $, QUnit ) {

	/**
	 * Values that are used in combination with the "precisions" object.
	 * @type {number[]}
	 */
	var values = [0, 0.06, 0.4, 0.5, 1, 10, 17];

	/**
	 * Keyed by the precision to apply, this object contains the results for the values specified in
	 * the "values" array.
	 * @type {Object}
	 */
	var precisions = {
		0: {
			toDecimal: [0, 0.06, 0.4, 0.5, 1, 10, 17],
			toDegree: [
				{ degree: 0, minute: 0, second: 0 },
				{ degree: 0, minute: 3, second: 36 },
				{ degree: 0, minute: 24, second: 0 },
				{ degree: 0, minute: 30, second: 0 },
				{ degree: 1, minute: 0, second: 0 },
				{ degree: 10, minute: 0, second: 0 },
				{ degree: 17, minute: 0, second: 0 }
			]
		},
		1: {
			toDecimal: [0, 0, 0, 1, 1, 10, 17],
			toDegree: [
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 1, minute: undefined, second: undefined },
				{ degree: 10, minute: undefined, second: undefined },
				{ degree: 17, minute: undefined, second: undefined }
			]
		},
		2: {
			toDecimal: [0, 0, 0, 1, 1, 10, 17],
			toDegree: [
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 2, minute: undefined, second: undefined },
				{ degree: 10, minute: undefined, second: undefined },
				{ degree: 18, minute: undefined, second: undefined }
			]
		},
		1.00000001: {
			toDecimal: [0, 0, 0, 1, 1, 10, 17],
			toDegree: [
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 1.00000001, minute: undefined, second: undefined },
				{ degree: 10.0000001, minute: undefined, second: undefined },
				{ degree: 17.00000017, minute: undefined, second: undefined }
			]
		},
		0.016666666666666666: {
			toDecimal: [0, 0.06, 0.4, 0.5, 1, 10, 17],
			toDegree: [
				{ degree: 0, minute: 0, second: undefined },
				{ degree: 0, minute: 3, second: undefined },
				{ degree: 0, minute: 24, second: undefined },
				{ degree: 0, minute: 30, second: undefined },
				{ degree: 1, minute: 0, second: undefined },
				{ degree: 10, minute: 0, second: undefined },
				{ degree: 17, minute: 0, second: undefined }
			]
		},
		2.7777777777777776e-7: {
			toDecimal: [0, 0.06, 0.4, 0.5, 1, 10, 17],
			toDegree: [
				{ degree: 0, minute: 0, second: 0 },
				{ degree: 0, minute: 3, second: 36 },
				{ degree: 0, minute: 24, second: 0 },
				{ degree: 0, minute: 30, second: 0 },
				{ degree: 1, minute: 0, second: 0 },
				{ degree: 10, minute: 0, second: 0 },
				{ degree: 17, minute: 0, second: 0 }
			]
		},
		1.0000000001e-10: {
			toDecimal: [0, 0.06, 0.4, 0.5, 1, 10, 17],
			toDegree: [
				{ degree: 0, minute: 0, second: 0 },
				{ degree: 0, minute: 3, second: 36 },
				{ degree: 0, minute: 24, second: 0 },
				{ degree: 0, minute: 30, second: 0 },
				{ degree: 1, minute: 0, second: 0 },
				{ degree: 10, minute: 0, second: 0 },
				{ degree: 17, minute: 0, second: 0 }
			]
		},
		1.0000001: {
			toDecimal: [0, 0, 0, 1, 1, 10, 17],
			toDegree: [
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 1.0000001, minute: undefined, second: undefined },
				{ degree: 10.000001, minute: undefined, second: undefined },
				{ degree: 17.0000017, minute: undefined, second: undefined }
			]
		},
		1.1: {
			toDecimal: [0, 0, 0, 1, 1, 10, 17],
			toDegree: [
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 1.1, minute: undefined, second: undefined },
				{ degree: 9.9, minute: undefined, second: undefined },
				{ degree: 16.5, minute: undefined, second: undefined }
			]
		},
		10: {
			toDecimal: [0, 0, 0, 0, 0, 10, 20],
			toDegree: [
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 10, minute: undefined, second: undefined },
				{ degree: 20, minute: undefined, second: undefined }
			]
		}
	};

	/**
	 * ISO 6709 representations keyed by the input string used to generate a GlobeCoordinate object.
	 * @type {Object}
	 */
	var iso6709representations = {
		'+00+000/': { latitude: 0, longitude: 0, precision: 1 },
		'-03+002/': { latitude: -3, longitude: 2, precision: 1 },
		'+0106+00200/': { latitude: 1.1, longitude: 2, precision: 0.1 },
		'+900000+0300600/': { latitude: 90, longitude: 30.1, precision: 0.01 },
		'+000600+0000027/': { latitude: 0.1, longitude: 0.0075, precision: 1 / 3600 },
		'-0006+00000/': { latitude: -0.1, longitude: 0, precision: 1 / 60 },
		'+010001+0000000/': { latitude: 1.00028, longitude: 0, precision: 1 / 3600 },
		'+010001.8+0000000.0/': { latitude: 1.0005, longitude: 0, precision: 1 / 36000 },
		'+895400.000-0000001.116/': { latitude: 89.9, longitude: -0.00031, precision: 1 / 3600000 },
		'+050000.0-0000010.5/': { latitude: 5, longitude: -0.00292, precision: 1 / 36000 }
	};

	QUnit.module( 'globeCoordinate.js' );

	QUnit.test( 'Applying precision', function( assert ) {

		$.each( precisions, function( precision, expected ) {
			$.each( values, function( i, value ) {

				assert.strictEqual(
					globeCoordinate.toDecimal( value, precision ),
					expected.toDecimal[i],
					'Applied precision \'' + precision + '\' to \'' + value + '\' resulting in \'' + expected.toDecimal[i] + '\'.'
				);

			} );
		} );

	} );

	QUnit.test( 'Converting to degree', function( assert ) {

		$.each( precisions, function( precision, expected ) {
			$.each( values, function( i, value ) {

				assert.deepEqual(
					globeCoordinate.toDegree( value, precision ),
					expected.toDegree[i],
					'Converted \'' + value + '\' to degree applying precision \'' + precision
						+ '\' resulting in: '
						+ expected.toDegree[i].degree + '° '
						+ expected.toDegree[i].minute + '" '
						+ expected.toDegree[i].second + '\'.'
				);

			} );
		} );

	} );

	QUnit.test( 'iso6709()', function( assert ) {

		$.each( iso6709representations, function( iso6709string, gcDef ) {
			assert.equal(
				globeCoordinate.iso6709( gcDef ),
				iso6709string,
				'Generated ISO 6709 string: \'' + iso6709string + '\'.'
			);
		} );

	} );

} );

}( define ) );

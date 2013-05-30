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

	var values = [0, 0.06, 0.4, 0.5, 1, 10];

	var precisions = {
		0: {
			tech: String.fromCharCode( 0x00B1 ) + '1e-9°',
			earth: '1 mm',
			increased: 1e-9,
			decreased: 1e-9,
			toDecimal: [0, 0.06, 0.4, 0.5, 1, 10],
			toDegree: [
				{ degree: 0, minute: 0, second: 0 },
				{ degree: 0, minute: 3, second: 36 },
				{ degree: 0, minute: 24, second: 0 },
				{ degree: 0, minute: 30, second: 0 },
				{ degree: 1, minute: 0, second: 0 },
				{ degree: 10, minute: 0, second: 0 }
			]
		},
		1: {
			tech: 1,
			earth: '100 km',
			increased: 0.1,
			decreased: 10,
			toDecimal: [0, 0, 0, 1, 1, 10],
			toDegree: [
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 1, minute: undefined, second: undefined },
				{ degree: 10, minute: undefined, second: undefined }
			]
		},
		2: {
			tech: String.fromCharCode( 0x00B1 ) + '2°',
			earth: '200 km',
			increased: 0.2,
			decreased: 20,
			toDecimal: [0, 0, 0, 1, 1, 10],
			toDegree: [
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 1, minute: undefined, second: undefined },
				{ degree: 10, minute: undefined, second: undefined }
			]
		},
		1.00000001: {
			tech: 1,
			earth: '100 km',
			increased: 0.10000000099999999,
			decreased: 10.0000001,
			toDecimal: [0, 0, 0, 1, 1, 10],
			toDegree: [
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 1, minute: undefined, second: undefined },
				{ degree: 10, minute: undefined, second: undefined }
			]
		},
		0.016666666666666666: {
			tech: 1 / 60,
			earth: '2 km',
			increased: 0.0016666666666666666,
			decreased: 0.16666666666666666,
			toDecimal: [0, 0.06, 0.4, 0.5, 1, 10],
			toDegree: [
				{ degree: 0, minute: 0, second: undefined },
				{ degree: 0, minute: 3, second: undefined },
				{ degree: 0, minute: 24, second: undefined },
				{ degree: 0, minute: 30, second: undefined },
				{ degree: 1, minute: 0, second: undefined },
				{ degree: 10, minute: 0, second: undefined }
			]
		},
		2.7777777777777776e-7: {
			tech: 1 / 3600000,
			earth: '3 cm',
			increased: 2.7777777777777777e-8,
			decreased: 0.0000027777777777777775,
			toDecimal: [0, 0.06, 0.4, 0.5, 1, 10],
			toDegree: [
				{ degree: 0, minute: 0, second: 0 },
				{ degree: 0, minute: 3, second: 36 },
				{ degree: 0, minute: 24, second: 0 },
				{ degree: 0, minute: 30, second: 0 },
				{ degree: 1, minute: 0, second: 0 },
				{ degree: 10, minute: 0, second: 0 }
			]
		},
		1.0000000001e-10: {
			tech: String.fromCharCode( 0x00B1 ) + '1e-9°',
			earth: '1 mm',
			increased: 1e-9,
			decreased: 1e-9,
			toDecimal: [0, 0.06, 0.4, 0.5, 1, 10],
			toDegree: [
				{ degree: 0, minute: 0, second: 0 },
				{ degree: 0, minute: 3, second: 36 },
				{ degree: 0, minute: 24, second: 0 },
				{ degree: 0, minute: 30, second: 0 },
				{ degree: 1, minute: 0, second: 0 },
				{ degree: 10, minute: 0, second: 0 }
			]
		},
		1.0000001: {
			tech: String.fromCharCode( 0x00B1 ) + '1.0000001°',
			earth: '100 km',
			increased: 0.10000001,
			decreased: 10.000001000000001,
			toDecimal: [0, 0, 0, 1, 1, 10],
			toDegree: [
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 1, minute: undefined, second: undefined },
				{ degree: 10, minute: undefined, second: undefined }
			]
		},
		1.1: {
			tech: String.fromCharCode( 0x00B1 ) + '1.1°',
			earth: '100 km',
			increased: 0.11000000000000001,
			decreased: 11,
			toDecimal: [0, 0, 0, 1, 1, 10],
			toDegree: [
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 0, minute: undefined, second: undefined },
				{ degree: 1, minute: undefined, second: undefined },
				{ degree: 10, minute: undefined, second: undefined }
			]
		}
	};

	QUnit.module( 'coordinate.js' );

	QUnit.test( 'precisionText()', function( assert ) {

		$.each( precisions, function( precision, expected ) {
			var precisionText = coordinate.precisionText( precision );

			// Look up precision text:
			if( typeof expected.tech === 'number' ) {

				$.each( coordinate.settings.precisions, function( i, precisionDefinition ) {
					if( precisionDefinition.level === expected.tech ) {

						assert.strictEqual(
							precisionText,
							precisionDefinition.text,
							'Precision text for \'' + precision + '\' results in text \''
								+ precisionDefinition.text + '\'.'
						);

						return false;
					}
				} );

			} else {

				assert.strictEqual(
					precisionText,
					expected.tech,
					'Precision text for \'' + precision + '\' results in text \'' + expected.tech + '\'.'
				);

			}

		} );

	} );

	QUnit.test( 'precisionTextEarth()', function( assert ) {

		$.each( precisions, function( precision, expected ) {

				assert.strictEqual(
					coordinate.precisionTextEarth( precision ),
					expected.earth,
					'Precision text for \'' + precision + '\' results in text \'' + expected.earth + '\'.'
				);

		} );

	} );

	QUnit.test( 'Increase and decrease precision', function( assert ) {

		$.each( precisions, function( precision, expected ) {

			assert.strictEqual(
				coordinate.increasePrecision( precision ),
				expected.increased,
				'Increased precision \'' + precision + '\' to \'' + expected.increased + '\'.'
			);

			assert.strictEqual(
				coordinate.decreasePrecision( precision ),
				expected.decreased,
				'Decreased precision \'' + precision + '\' to \'' + expected.decreased + '\'.'
			);

		} );

	} );

	QUnit.test( 'Applying precision', function( assert ) {

		$.each( precisions, function( precision, expected ) {
			$.each( values, function( i, value ) {

				assert.strictEqual(
					coordinate.toDecimal( value, precision ),
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
					coordinate.toDegree( value, precision ),
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

	QUnit.test( 'Text output', function( assert ) {

		// Just some output sanity checking:

		// decimalText():

		assert.equal(
			coordinate.decimalText( 0, 0, 0 ),
			'0° N, 0° E',
			'Verified output: 0° N, 0° E'
		);

		assert.equal(
			coordinate.decimalText( 1, 1, 1 ),
			'1° N, 1° E',
			'Verified output: 1° N, 1° E'
		);

		assert.equal(
			coordinate.decimalText( -10, -1.5, 0.1 ),
			'10° S, 1.5° W',
			'Verified output: 10° S, 1.5° W'
		);

		// degreeText():

		assert.equal(
			coordinate.degreeText( 0, 0, 0 ),
			'0°0\'0"N, 0°0\'0"E',
			'Verified output: 0°0\'0"N, 0°0\'0"E'
		);

		assert.equal(
			coordinate.degreeText( 1, 1, 1 ),
			'1°N, 1°E',
			'Verified output: 1°N, 1°E'
		);

		assert.equal(
			coordinate.degreeText( -10, -1.5, 0.1 ),
			'10°0\'S, 2°30\'W',
			'Verified output: 10°0\'S, 2°30\'W'
		);

	} );

}( QUnit, jQuery, coordinate ) );

/**
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
define( ['time/time.Time', 'jquery', 'qunit'], function( Time, $, QUnit ) {
	'use strict';

	QUnit.module( 'Time.js: time.Time.maxPrecision' );

	QUnit.test( 'time.Time.maxPrecision() return value', function( assert ) {
		var maxPrecision = Time.maxPrecision();

		assert.ok(
			typeof maxPrecision === 'number',
			'returns a number'
		);

		assert.ok(
			!isNaN( maxPrecision ),
			'return value is not NaN'
		);
	} );

	QUnit.test( 'maxPrecision accuracy', function( assert ) {
		var precisionKey, precision,
			maxDetermined = Number.NEGATIVE_INFINITY;

		for( precisionKey in Time.PRECISION ) {
			precision = Time.PRECISION[ precisionKey ];

			assert.ok(
				precision <= Time.maxPrecision(),
				'precision "' + precisionKey + '" smaller or equal time.Time.maxPrecision()'
			);

			if( precision > maxDetermined ) {
				maxDetermined = precision;
			}
		}

		assert.strictEqual(
			Time.maxPrecision(),
			maxDetermined,
			'time.Time.maxPrecision() returns highest number within the precision enum'
		);
	} );

} );

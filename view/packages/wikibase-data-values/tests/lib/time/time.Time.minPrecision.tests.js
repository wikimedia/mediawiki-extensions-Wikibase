/**
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
define( ['time/time.Time', 'jquery', 'qunit'], function( Time, $, QUnit ) {
	'use strict';

	QUnit.module( 'Time.js: time.Time.minPrecision' );

	QUnit.test( 'time.Time.minPrecision() return value', function( assert ) {
		var minPrecision = Time.minPrecision();

		assert.ok(
			typeof minPrecision === 'number',
			'returns a number'
		);

		assert.ok(
			!isNaN( minPrecision ),
			'return value is not NaN'
		);
	} );

	QUnit.test( 'minPrecision accuracy', function( assert ) {
		var precisionKey, precision,
			minDetermined = Number.POSITIVE_INFINITY;

		for( precisionKey in Time.PRECISION ) {
			precision = Time.PRECISION[ precisionKey ];

			assert.ok(
				precision >= Time.minPrecision(),
				'precision "' + precisionKey + '" greater or equal time.Time.minPrecision()'
			);

			if( precision < minDetermined ) {
				minDetermined = precision;
			}
		}

		assert.strictEqual(
			Time.minPrecision(),
			minDetermined,
			'time.Time.minPrecision() returns lowest number within the precision enum'
		);
	} );

} );

/**
 * @since 0.1
 * @file
 * @ingroup Time.js
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( QUnit, jQuery, Time ) {
	'use strict';

	QUnit.module( 'Time.js: time.Time.knowsPrecision' );

	QUnit.test( 'all known precisions', function( assert ) {
		var precision;

		for( precision in Time.PRECISION ) {
			assert.ok(
				Time.knowsPrecision( Time.PRECISION[ precision ] ),
				'time.Time.PRECISION.' + precision + ' is a known precision'
			);
		}
	} );

	QUnit.test( 'invalid precisions', function( assert ) {
		assert.ok(
			!Time.knowsPrecision( Time.maxPrecision() + 1 ),
			'Precision above highest precision is an unknown precision'
		);
		assert.ok(
			!Time.knowsPrecision( Time.minPrecision() - 1 ),
			'Precision below lowest precision is an unknown precision'
		);
		assert.ok(
			!Time.knowsPrecision( 'foo' ),
			'Random string is not a known precision'
		);
		assert.ok(
			!Time.knowsPrecision( Number.NaN ),
			'NaN is not a known precision'
		);
	} );

}( QUnit, jQuery, time.Time ) );

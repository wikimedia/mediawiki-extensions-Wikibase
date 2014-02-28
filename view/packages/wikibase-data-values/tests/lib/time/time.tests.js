/**
 * @licence GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */
( function( define ) {
'use strict';

var DEPS = [
	'time',
	'jquery',
	'qunit',
];

define( DEPS, function( time, $, QUnit ) {

	QUnit.module( 'Time.js: time' );

	var validYears = [
		-9784, -1, 0, 5, 500, 12454
	];

	var validMonths = [
		1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12
	];

	var validDays = [
		1, 4, 5, 10, 11, 31
	];

	var validPrecisions = [
		0, 1, 2, 3, 4, 5, 6, 7, 8
	];

	QUnit.test( 'return type of writeYear', function( assert ) {
		$.each( validYears, function( k, y ) {
			assert.equal( typeof time.writeYear( y ), 'string',
				'Return type of writeYear( ' + y + ' ) is string' );
		} );
	} );

	QUnit.test( 'return type of writeApproximateYear', function( assert ) {
		$.each( validYears, function( k, y ) {
			$.each( validPrecisions, function( k, precision ) {
				assert.equal( typeof time.writeApproximateYear( y, precision ), 'string',
					'Return type of writeApproximateYear( ' + y + ', ' + precision + ' ) is string' );
			} );
		} );
	} );

	QUnit.test( 'return type of writeMonth', function( assert ) {
		$.each( validMonths, function( k, m ) {
			assert.equal( typeof time.writeMonth( m ), 'string',
				'Return type of writeMonth( ' + m + ' ) is string' );
		} );
	} );

	QUnit.test( 'return type of writeDay', function( assert ) {
		$.each( validDays, function( k, d ) {
			assert.equal( typeof time.writeDay( d ), 'string',
				'Return type of writeDay( ' + d + ' ) is string' );
		} );
	} );
} );

}( define ) );

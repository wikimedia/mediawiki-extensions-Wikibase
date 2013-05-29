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

	QUnit.module( 'coordinate.Coordinate.js' );

	QUnit.test( 'Basic checks', function( assert ) {
		var c;

		assert.throws(
			function() { c = new coordinate.Coordinate( '' ); },
			'Trying to instantiate with an empty value throws an error.'
		);

		assert.throws(
			function() { c = new coordinate.Coordinate( 'some string' ); },
			'Trying to instantiate with an invalid value throws an error.'
		);

		c = new coordinate.Coordinate( '1.5 1.5' );

		// Since most methods are just plain getters, just doing plain verification:

		assert.equal(
			c.getRawInput(),
			'1.5 1.5',
			'Verified getRawInput()'
		);

		assert.equal(
			c.getLatitude(),
			1.5,
			'Verified getLatitude()'
		);

		assert.equal(
			c.getLongitude(),
			1.5,
			'Verified getLongitude()'
		);

		assert.equal(
			c.getPrecision(),
			0.1,
			'Verified getPrecision()'
		);

		assert.equal(
			typeof c.getPrecisionText(),
			'string',
			'Verified getPrecisionText()'
		);

		assert.equal(
			typeof c.getPrecisionTextEarth(),
			'string',
			'Verified getPrecisionTextEarth()'
		);

		assert.equal(
			c.latitudeDecimal(),
			1.5,
			'Verified latitudeDecimal()'
		);

		assert.equal(
			c.longitudeDecimal(),
			1.5,
			'Verified longitudeDecimal()'
		);

		assert.deepEqual(
			c.latitudeDegree(),
			{ degree: 1, minute: 30, second: undefined },
			'Verified latitudeDegree()'
		);

		assert.deepEqual(
			c.longitudeDegree(),
			{ degree: 1, minute: 30, second: undefined },
			'Verified longitudeDegree()'
		);

		assert.equal(
			typeof c.decimalText(),
			'string',
			'Verified decimalText()'
		);

		assert.equal(
			typeof c.degreeText(),
			'string',
			'Verified degreeText()'
		);

	} );

	QUnit.test( 'isValid()', function( assert ) {
		var c = new coordinate.Coordinate( '1.5 1.25' );

		assert.ok(
			c.isValid(),
			'\'1.5 1.25\' generates a valid coordinate.'
		);

		c = new coordinate.Coordinate( '190° 30" 1.123\'' );

		assert.ok(
			!c.isValid(),
			'190° 30" 1.123\' generates an invalid coordinate.'
		);

	} );

	QUnit.test( 'Precision handling', function( assert ) {
		var c = new coordinate.Coordinate( '1.5 1.25' );

		assert.equal(
			c.getPrecision(),
			0.01,
			'Increased precision'
		);

		c.decreasePrecision();
		c.decreasePrecision();

		assert.equal(
			c.getPrecision(),
			0.1,
			'Decreased precision'
		);

		assert.equal(
			c.longitudeDecimal(),
			1.3,
			'Verified applied precision'
		);

		c.increasePrecision();
		c.increasePrecision();

		assert.equal(
			c.getPrecision(),
			0.01,
			'Increased precision'
		);

		assert.equal(
			c.longitudeDecimal(),
			1.25,
			'Verified applied precision'
		);

		c.setPrecision( 1 );

		assert.equal(
			c.getPrecision(),
			1,
			'Set precision'
		);

		assert.equal(
			c.longitudeDecimal(),
			1,
			'Verified applied precision'
		);

	} );


}( QUnit, jQuery, coordinate ) );

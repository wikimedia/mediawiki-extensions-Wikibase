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
	 * ISO 6709 representations keyed by the input string used to generate a coordinate object.
	 * @type {Object}
	 */
	var iso6709representations = {
		'0': '+00+000/',
		'-3 +2': '-03+002/',
		'1.1 2': '+0106+002/',
		'90° N 30.10°': '+90+03006/',
		'0° 5\'N, 0° 0\' 10"E': '+0005+0000010/',
		'5\'S': '-05+000/',
		'1\' 1"': '+010001+000/',
		'1\' 1.1"': '+010001.1+000/',
		'190° 30" 1.123\'': '+19030+0010723/',
		'5\'N 0\' 10.5"W': '+05-0015949.5/'
	};

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

	QUnit.test( 'iso6709()', function( assert ) {
		var c;

		$.each( iso6709representations, function( inputString, iso6709string ) {
			c = new coordinate.Coordinate( inputString );

			assert.equal(
				c.iso6709(),
				iso6709string,
				'Validated ISO 6709 string for \'' + inputString + '\': \'' + iso6709string + '\'.'
			);

		} );

	} );

	QUnit.test( 'equals()', function( assert ) {
		var c1, c2;

		$.each( iso6709representations, function( inputString1, iso6709string1 ) {
			c1 = new coordinate.Coordinate( inputString1 );

			$.each( iso6709representations, function( inputString2, iso6709string2 ) {
				c2 = new coordinate.Coordinate( inputString2 );

				if( inputString1 === inputString2 && c1.isValid() && c2.isValid() ) {

					assert.ok(
						c1.equals( c2 ),
						'Validated equality for \'' + inputString1 + '\'.'
					);

				} else {

					assert.ok(
						!c1.equals( c2 ),
						'Validated inequality of \'' + inputString1 + '\' and \'' + inputString2 + '\'.'
					);

				}

			} );

		} );

	} );

}( QUnit, jQuery, coordinate ) );

/**
 * @since 0.1
 * @file
 * @ingroup globeCoordinate.js
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( QUnit, $, globeCoordinate ) {
	'use strict';

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

	QUnit.module( 'globeCoordinate.GlobeCoordinate.js' );

	QUnit.test( 'Basic checks', function( assert ) {
		var c;

		assert.throws(
			function() { c = new globeCoordinate.GlobeCoordinate( '' ); },
			'Trying to instantiate with an empty value throws an error.'
		);

		assert.throws(
			function() { c = new globeCoordinate.GlobeCoordinate( 'some string' ); },
			'Trying to instantiate with an invalid value (some string) throws an error.'
		);

		assert.throws(
			function() { c = new globeCoordinate.GlobeCoordinate( '190° 30" 1.123\'' ); },
			'Trying to instantiate with an invalid value (190° 30" 1.123\') throws an error.'
		);

		assert.throws(
			function() { c = new globeCoordinate.GlobeCoordinate( { latitude: 20 } ); },
			'Trying to instantiate with an invalid value ({ latitude: 20 }) throws an error.'
		);

		c = new globeCoordinate.GlobeCoordinate( { latitude: 1.5, longitude: 1.5, precision: 0.1 } );

		// Since most methods are just plain getters, just doing plain verification:

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

	QUnit.test( 'iso6709()', function( assert ) {
		var c;

		$.each( iso6709representations, function( iso6709string, gcDef ) {
			c = new globeCoordinate.GlobeCoordinate( gcDef );

			assert.equal(
				c.iso6709(),
				iso6709string,
				'Validated ISO 6709 string for \'' + c.decimalText() + '\': \'' + iso6709string + '\'.'
			);

		} );

	} );

	QUnit.test( 'equals()', function( assert ) {
		var c1, c2;

		$.each( iso6709representations, function( iso6709string1, gcDef1 ) {
			c1 = new globeCoordinate.GlobeCoordinate( gcDef1 );

			$.each( iso6709representations, function( iso6709string2, gcDef ) {
				c2 = new globeCoordinate.GlobeCoordinate( gcDef );

				if( iso6709string1 === iso6709string2 ) {

					assert.ok(
						c1.equals( c2 ),
						'Validated equality for \'' + c1.decimalText() + '\'.'
					);

				} else {

					assert.ok(
						!c1.equals( c2 ),
						'Validated inequality of \'' + c1.decimalText() + '\' and \'' + c2.decimalText() + '\'.'
					);

				}

			} );

		} );

	} );

}( QUnit, jQuery, globeCoordinate ) );

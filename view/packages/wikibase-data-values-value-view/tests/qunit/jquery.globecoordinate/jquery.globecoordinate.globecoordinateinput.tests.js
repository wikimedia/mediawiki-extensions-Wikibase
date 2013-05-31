/**
 * @since 0.1
 * @ingroup ValueView
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( $, GlobeCoordinate, QUnit ) {
	'use strict';

	/**
	 * Factory for creating a jQuery.globecoordinate.globecoordinateinput widget suitable for
	 * testing.
	 */
	var newTestGlobeCoordinateinput = function( options ) {
		return $( '<input/>' ).addClass( 'test_globecoordinateinput' ).globecoordinateinput( options );
	};

	QUnit.module( 'jquery.globecoordinate.globecoordinateinput', {
		teardown: function() {
			$( '.test_globecoordinateinput' ).remove();
		}
	} );

	QUnit.test( 'Input interpretation', function( assert ) {
		var $input = newTestGlobeCoordinateinput(),
			inputEvent = $.Event( 'input' ),
			keydownEvent = $.Event( 'keydown' );

		var assertValue = function( value ) {
			assert.ok(
				value instanceof GlobeCoordinate,
				'Recognized globe coordinate value.'
			);
		};

		$input.on( 'globecoordinateinputupdate', function( event, value ) {
			assertValue( value );
		} );

		// Just test a valid globe coordinate string (no need to test the globe coordinate parser).
		// Issuing "input" and "keydown" event to trigger "eachchange" in the various browsers.
		$input.val( '30, 30' ).trigger( inputEvent ).trigger( keydownEvent );

		assertValue = function( value ) {
			assert.strictEqual(
				value,
				null,
				'Emptied input value.'
			);
		};

		$input.val( '' ).trigger( inputEvent ).trigger( keydownEvent );
	} );

	QUnit.test( 'value()', function( assert ) {
		var $input = newTestGlobeCoordinateinput();

		assert.strictEqual(
			$input.data( 'globecoordinateinput' ).value(),
			null,
			'No value set.'
		);

		assert.throws(
			function() {
				$input.data( 'globecoordinateinput' ).value( 'asdf' );
			},
			Error,
			'Throwing error when trying to set incompatible value.'
		);

		assert.ok(
			$input.data( 'globecoordinateinput' ).value( new GlobeCoordinate( '30, 30' ) )
				instanceof GlobeCoordinate,
			'Set globe coordinate value.'
		);

		assert.ok(
			$input.data( 'globecoordinateinput' ).value() instanceof GlobeCoordinate,
			'Checked set globe coordinate value.'
		);

	} );

}( jQuery, globeCoordinate.GlobeCoordinate, QUnit ) );

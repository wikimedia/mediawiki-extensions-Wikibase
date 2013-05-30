/**
 * @since 0.1
 * @ingroup ValueView
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( $, Coordinate, QUnit ) {
	'use strict';

	/**
	 * Factory for creating a jQuery.coordinate.coordinateinput widget suitable for testing.
	 */
	var newTestCoordinateinput = function( options ) {
		return $( '<input/>' ).addClass( 'test_coordinateinput' ).coordinateinput( options );
	};

	QUnit.module( 'jquery.coordinate.coordinateinput', {
		teardown: function() {
			$( '.test_coordinateinput' ).remove();
		}
	} );

	QUnit.test( 'Input interpretation', function( assert ) {
		var $input = newTestCoordinateinput(),
			inputEvent = $.Event( 'input' ),
			keydownEvent = $.Event( 'keydown' );

		var assertValue = function( value ) {
			assert.ok(
				value instanceof Coordinate,
				'Recognized coordinate value.'
			);
		};

		$input.on( 'coordinateinputupdate', function( event, value ) {
			assertValue( value );
		} );

		// Just test a valid coordinate string (no need to test the coordinate parser).
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
		var $input = newTestCoordinateinput();

		assert.strictEqual(
			$input.data( 'coordinateinput' ).value(),
			null,
			'No value set.'
		);

		assert.throws(
			function() {
				$input.data( 'coordinateinput' ).value( 'asdf' );
			},
			Error,
			'Throwing error when trying to set incompatible value.'
		);

		assert.ok(
			$input.data( 'coordinateinput' ).value( new Coordinate( '30, 30' ) )
				instanceof Coordinate,
			'Set coordinate value.'
		);

		assert.ok(
			$input.data( 'coordinateinput' ).value() instanceof Coordinate,
			'Checked set coordinate value.'
		);

	} );

}( jQuery, coordinate.Coordinate, QUnit ) );

/**
 * @since 0.1
 * @ingroup ValueView
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( $, Time, QUnit ) {
	'use strict';

	/**
	 * Factory for creating a jQuery.time.timeinput widget suitable for testing.
	 */
	var newTestTimeinput = function( options ) {
		return $( '<input/>' ).addClass( 'test_timeinput' ).timeinput( options );
	};

	QUnit.module( 'jquery.time.timeinput', {
		teardown: function() {
			$( '.test_timeinput' ).remove();
		}
	} );

	QUnit.test( 'Input interpretation', function( assert ) {
		var $input = newTestTimeinput(),
			inputEvent = $.Event( 'input' ),
			keydownEvent = $.Event( 'keydown' );

		var assertValue = function( value ) {
			assert.ok(
				value instanceof Time,
				'Recognized time value.'
			);
		};

		$input.on( 'timeinputupdate', function( event, value ) {
			assertValue( value );
		} );

		// Just test a valid date string (no use for testing the time parser).
		// Issuing "input" and "keydown" event to trigger "eachchange" in the various browsers.
		$input.val( '1.1.1980' ).trigger( inputEvent ).trigger( keydownEvent );

		assertValue = function( value ) {
			assert.equal(
				value,
				null,
				'Emptied input value.'
			);
		};

		$input.val( '' ).trigger( inputEvent ).trigger( keydownEvent );
	} );

	QUnit.test( 'value()', function( assert ) {
		var $input = newTestTimeinput();

		assert.equal(
			$input.data( 'timeinput' ).value(),
			null,
			'No value set.'
		);

		assert.throws(
			function() {
				$input.data( 'timeinput' ).value( 'asdf' );
			},
			Error,
			'Throwing error when trying to set incompatible value.'
		);

		assert.ok(
			$input.data( 'timeinput' ).value( new Time( '1.1.1980' ) ) instanceof Time,
			'Set time value.'
		);

		assert.ok(
			$input.data( 'timeinput' ).value() instanceof Time,
			'Checked set time value.'
		);

	} );

}( jQuery, time.Time, QUnit ) );

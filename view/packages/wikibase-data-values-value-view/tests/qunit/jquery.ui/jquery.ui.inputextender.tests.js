/**
 * @since 0.1
 * @ingroup ValueView
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( $, QUnit ) {
	'use strict';

	// TODO: Tests for toggling additional contents
	// TODO: Tests for hideWhenInputEmptyOption

	/**
	 * Factory for creating an input extender widget suitable for testing.
	 */
	var newTestInputextender = function( options ) {
		if( !options ) {
			options = {
				content: [ $( '<span/>' ).addClass( 'defaultContent' ).text( 'default content' ) ],
				extendedContent: [ $( '<span/>' ).addClass( 'extendedContent' ).text( 'extended content' ) ]
			}
		}

		return $( '<input/>' )
			.addClass( 'test_inputextender' )
			.appendTo( $( 'body' ) )
			.inputextender( options );
	};

	QUnit.module( 'jquery.ui.inputextender', QUnit.newMwEnvironment( {
		teardown: function() {
			$( '.test_inputextender' ).each( function( i, node ) {
				$( node ).data( 'inputextender' ).destroy();
				$( node ).remove();
			} );
		}
	} ) );

	QUnit.test( 'Initialization', 1, function( assert ) {
		var $input = newTestInputextender(),
			extender = $input.data( 'inputextender' );

		assert.ok(
			!extender.$contentContainer.is( ':visible' ),
			'Content is not visible.'
		);
	} );

	QUnit.test( 'Show/Hide basic content', 4, function( assert ) {
		var $input = newTestInputextender(),
			extender = $input.data( 'inputextender' );

		extender.showContent( function() {
			assert.ok(
				extender.$contentContainer.is( ':visible' ),
				'Content visible after focusing input element.'
			);

			assert.ok(
				extender.$content.is( ':visible' ),
				'Default content is visible.'
			);

			assert.ok(
				!extender.$extendedContent.is( ':visible' ),
				'Additional content is hidden.'
			);
		} );

		QUnit.stop();

		extender.hideContent( function() {
			assert.ok(
				!extender.$contentContainer.is( ':visible' ),
				'Content is hidden after blurring the input element.'
			);

			QUnit.start();
		} );

	} );

}( jQuery, QUnit ) );

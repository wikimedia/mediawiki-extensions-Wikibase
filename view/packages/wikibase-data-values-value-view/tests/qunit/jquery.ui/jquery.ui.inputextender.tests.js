/**
 * @since 0.1
 * @ingroup ValueView
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( $, QUnit ) {
	'use strict';

	// TODO: Tests for hideWhenInputEmptyOption

	/**
	 * Factory for creating an input extender widget suitable for testing.
	 */
	var newTestInputextender = function( options ) {
		if( !options ) {
			options = {
				content: [ $( '<span/>' ).addClass( 'defaultContent' ).text( 'default content' ) ]
			};
		}

		var $input = $( '<input/>' )
			.addClass( 'test_inputextender' )
			.appendTo( $( 'body' ) )
			.inputextender( options );

		return $input.data( 'inputextender' );
	};

	QUnit.module( 'jquery.ui.inputextender', {
		teardown: function() {
			$( '.test_inputextender' ).each( function( i, node ) {
				if( $( node ).data( 'inputextender' ) ) {
					$( node ).data( 'inputextender' ).destroy();
				}
				$( node ).remove();
			} );
		}
	} );

	QUnit.test( 'Initialization', 4, function( assert ) {
		var extender = newTestInputextender(),
			widgetBaseClass = extender.widgetBaseClass;

		assert.equal(
			$( '.test_inputextender' ).data( 'inputextender' ),
			extender,
			'Initialized widget.'
		);

		assert.ok(
			!extender.$extension.is( ':visible' ),
			'Extension is not visible.'
		);

		extender.destroy();

		assert.ok(
			$( '.test_inputextender' ).data( 'inputextender' ) === undefined,
			'Destroyed widget.'
		);

		assert.equal(
			$( '.' + widgetBaseClass + '-extension' ).length,
			0,
			'Removed extension node from DOM.'
		);
	} );

	QUnit.test( 'Show/Hide', 2, function( assert ) {
		var extender = newTestInputextender();

		QUnit.stop();

		extender.showExtension( function() {
			assert.ok(
				extender.$extension.is( ':visible' ),
				'showExtension()'
			);

			QUnit.stop();

			extender.hideExtension( function() {
				assert.ok(
					!extender.$extension.is( ':visible' ),
					'hideExtension()'
				);

				QUnit.start();
			} );

			QUnit.start();
		} );
	} );

}( jQuery, QUnit ) );

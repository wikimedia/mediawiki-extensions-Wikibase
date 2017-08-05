/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki at snater.com >
 */
( function ( $, QUnit ) {
	'use strict';

	/**
	 * @param {Object} [options]
	 * @return {jQuery}
	 */
	var createTestButton = function ( options ) {
		return $( '<span/>' )
			.addClass( 'test_toolbarbutton' )
			.toolbarbutton( options || {} );
	};

	QUnit.module( 'jquery.wikibase.toolbarbutton', QUnit.newMwEnvironment( {
		teardown: function () {
			$( 'test_toolbarbutton' ).each( function () {
				var $button = $( this ),
					button = $button.data( 'toolbarbutton' );

				if ( button ) {
					button.destroy();
				}

				$button.remove();
			} );
		}
	} ) );

	QUnit.test( 'Create & destroy', function ( assert ) {
		assert.expect( 2 );
		var $button = createTestButton(),
			button = $button.data( 'toolbarbutton' );

		assert.ok(
			button instanceof $.wikibase.toolbarbutton,
			'Instantiated widget.'
		);

		button.destroy();

		assert.ok(
			$button.data( 'button' ) === undefined,
			'Destroyed widget.'
		);
	} );

	QUnit.test( 'action event', function ( assert ) {
		var $button = createTestButton(),
			button = $button.data( 'toolbarbutton' );

		$button.on( 'toolbarbuttonaction', function ( event ) {
			assert.ok(
				$( event.target ).data( 'toolbarbutton' ) === button,
				'Triggered "action" event.'
			);
		} );

		$button.children( 'a' ).trigger( 'click' );
	} );

}( jQuery, QUnit ) );

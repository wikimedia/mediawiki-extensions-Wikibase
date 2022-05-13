/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki at snater.com >
 */
( function () {
	'use strict';

	/**
	 * @param {Object} [options]
	 * @return {jQuery}
	 */
	var createTestButton = function ( options ) {
		return $( '<span>' )
			.addClass( 'test_toolbarbutton' )
			.toolbarbutton( options || {} );
	};

	QUnit.module( 'jquery.wikibase.toolbarbutton', QUnit.newMwEnvironment( {
		afterEach: function () {
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
		var $button = createTestButton(),
			button = $button.data( 'toolbarbutton' );

		assert.true(
			button instanceof $.wikibase.toolbarbutton,
			'Instantiated widget.'
		);

		button.destroy();

		assert.strictEqual(
			$button.data( 'button' ),
			undefined,
			'Destroyed widget.'
		);
	} );

}() );

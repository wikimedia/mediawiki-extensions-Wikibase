/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function () {
	'use strict';

	QUnit.module( 'jquery.wikibase.removetoolbar', QUnit.newMwEnvironment( {
		afterEach: function () {
			$( '.test_removetoolbar' ).each( function () {
				var $removetoolbar = $( this ),
					removetoolbar = $removetoolbar.data( 'removetoolbar' );

				if ( removetoolbar ) {
					removetoolbar.destroy();
				}

				$removetoolbar.remove();
			} );
		}
	} ) );

	/**
	 * @param {Object} [options]
	 * @return {jQuery}
	 */
	function createRemovetoolbar( options ) {
		return $( '<span>' )
			.addClass( 'test_removetoolbar' )
			.removetoolbar( options || {} );
	}

	QUnit.test( 'Create & destroy', function ( assert ) {
		var $removetoolbar = createRemovetoolbar(),
			removetoolbar = $removetoolbar.data( 'removetoolbar' );

		assert.true(
			removetoolbar instanceof $.wikibase.removetoolbar,
			'Instantiated widget.'
		);

		removetoolbar.destroy();

		assert.strictEqual(
			$removetoolbar.data( 'removetoolbar' ),
			undefined,
			'Destroyed widget.'
		);
	} );

}() );

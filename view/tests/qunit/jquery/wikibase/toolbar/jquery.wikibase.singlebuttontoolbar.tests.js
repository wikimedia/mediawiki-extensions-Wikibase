/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function () {
	'use strict';

	QUnit.module( 'jquery.wikibase.singlebuttontoolbar', QUnit.newMwEnvironment( {
		afterEach: function () {
			$( '.test_singlebuttontoolbar' ).each( function () {
				var $singlebuttontoolbar = $( this ),
					singlebuttontoolbar = $singlebuttontoolbar.data( 'singlebuttontoolbar' );

				if ( singlebuttontoolbar ) {
					singlebuttontoolbar.destroy();
				}

				$singlebuttontoolbar.remove();
			} );
		}
	} ) );

	/**
	 * @param {Object} [options]
	 * @return {jQuery}
	 */
	function createSinglebuttontoolbar( options ) {
		return $( '<span>' )
			.addClass( 'test_singlebuttontoolbar' )
			.singlebuttontoolbar( options || {} );
	}

	QUnit.test( 'Create & destroy', function ( assert ) {
		var $singlebuttontoolbar = createSinglebuttontoolbar(),
			singlebuttontoolbar = $singlebuttontoolbar.data( 'singlebuttontoolbar' );

		assert.true(
			singlebuttontoolbar instanceof $.wikibase.singlebuttontoolbar,
			'Instantiated widget.'
		);

		singlebuttontoolbar.destroy();

		assert.strictEqual(
			$singlebuttontoolbar.data( 'singlebuttontoolbar' ),
			undefined,
			'Destroyed widget.'
		);
	} );

}() );

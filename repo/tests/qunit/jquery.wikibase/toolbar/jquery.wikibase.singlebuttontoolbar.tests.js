/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, QUnit ) {
	'use strict';

QUnit.module( 'jquery.wikibase.singlebuttontoolbar', QUnit.newMwEnvironment( {
	teardown: function() {
		$( '.test_singlebuttontoolbar' ).each( function() {
			var $singlebuttontoolbar = $( this ),
				singlebuttontoolbar = $singlebuttontoolbar.data( 'singlebuttontoolbar' );

			if( singlebuttontoolbar ) {
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
	return $( '<span/>' )
		.addClass( 'test_singlebuttontoolbar' )
		.singlebuttontoolbar( options || {} );
}

QUnit.test( 'Create & destroy', function( assert ) {
	var $singlebuttontoolbar = createSinglebuttontoolbar(),
		singlebuttontoolbar = $singlebuttontoolbar.data( 'singlebuttontoolbar' );

	assert.ok(
		singlebuttontoolbar instanceof $.wikibase.singlebuttontoolbar,
		'Instantiated widget.'
	);

	singlebuttontoolbar.destroy();

	assert.ok(
		!$singlebuttontoolbar.data( 'singlebuttontoolbar' ),
		'Destroyed widget.'
	);
} );

}( jQuery, QUnit ) );

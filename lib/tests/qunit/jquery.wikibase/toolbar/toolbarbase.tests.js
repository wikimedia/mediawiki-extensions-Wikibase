/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( $, QUnit ) {
'use strict';

QUnit.module( 'jQuery.wikibase.toolbarbase', QUnit.newMwEnvironment() );

QUnit.test( 'Create and destroy', function( assert ) {
	var $toolbarbase = $( '<span/>' ).toolbarbase(),
		toolbarbase = $toolbarbase.data( 'toolbarbase' );

	assert.ok(
		toolbarbase !== undefined,
		'Initialized toolbarbase widget.'
	);

	assert.ok(
		$toolbarbase.children().length !== 0,
		'Created and applied toolbar DOM structure.'
	);

	toolbarbase.destroy();

	assert.ok(
		$toolbarbase.data( 'toolbarbase' ) === undefined,
		'Destroyed widget.'
	);

	assert.ok(
		$toolbarbase.children().length === 0,
		'Removed toolbar DOM structure.'
	);
} );

QUnit.test( '$container option', function( assert ) {
	var $container = $( '<div/>' ),
		$toolbarbase = $( '<span/>' ).toolbarbase( {
			$container: $container
		} ),
		toolbarbase = $toolbarbase.data( 'toolbarbase' );

	assert.ok(
		$container.children().length > 0,
		'Appended toolbar DOM to container node.'
	);

	assert.ok(
		$toolbarbase.children().length === 0,
		'Node toolbarbase is initialized on does not have toolbar DOM.'
	);

	toolbarbase.destroy();

	assert.ok(
		$container.children().length === 0,
		'Removed toolbar DOM from container node.'
	);

	assert.ok(
		$toolbarbase.children().length === 0,
		'Node toolbarbase is initialized on still does not have toolbar DOM.'
	);
} );

}( jQuery, QUnit ) );

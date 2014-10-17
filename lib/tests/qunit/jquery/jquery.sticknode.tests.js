/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, QUnit ) {
	'use strict';

QUnit.module( 'jquery.sticknode' );

QUnit.test( 'Create & destroy', function( assert ) {
	var $node = $( '<div/>' ).sticknode();

	assert.ok(
		$node.data( 'sticknode' ) !== undefined,
		'Attached plugin.'
	);

	$node.data( 'sticknode' ).destroy();

	assert.ok(
		$node.data( 'sticknode' ) === undefined,
		'Detached plugin.'
	);
} );

}( jQuery, QUnit ) );

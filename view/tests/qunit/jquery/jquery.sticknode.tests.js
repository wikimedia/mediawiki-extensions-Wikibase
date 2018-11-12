/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function () {
	'use strict';

	QUnit.module( 'jquery.sticknode' );

	QUnit.test( 'Create & destroy', function ( assert ) {
		var $node = $( '<div/>' ).sticknode();

		assert.notStrictEqual(
			$node.data( 'sticknode' ),
			undefined,
			'Attached plugin.'
		);

		$node.data( 'sticknode' ).destroy();

		assert.strictEqual(
			$node.data( 'sticknode' ),
			undefined,
			'Detached plugin.'
		);
	} );

}() );

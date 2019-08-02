/**
 * @license GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( $, QUnit ) {
	'use strict';

	QUnit.module( 'jquery.util.getscrollbarwidth' );

	QUnit.test( 'Get scrollbar width', function( assert ) {
		assert.expect( 1 );

		assert.ok(
			$.util.getscrollbarwidth() > 0,
			'Retrieved scrollbar width.'
		);

	} );

}( jQuery, QUnit ) );

/**
 * @license GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( $, QUnit ) {
	'use strict';

	var getscrollbarwidth = require( '../../../../resources/lib/jquery.util/jquery.util.getscrollbarwidth.js' );

	QUnit.module( 'jquery.util.getscrollbarwidth' );

	QUnit.test( 'Get scrollbar width', function( assert ) {
		assert.expect( 1 );

		assert.ok(
			getscrollbarwidth() > 0,
			'Retrieved scrollbar width.'
		);

	} );

}( jQuery, QUnit ) );

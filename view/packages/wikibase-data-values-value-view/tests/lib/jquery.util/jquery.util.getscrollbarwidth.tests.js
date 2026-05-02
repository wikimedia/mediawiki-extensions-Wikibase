/**
 * @license GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function () {
	'use strict';

	QUnit.module( 'jquery.util.getscrollbarwidth' );

	QUnit.test( 'Get scrollbar width', function( assert ) {
		assert.ok(
			$.util.getscrollbarwidth() > 0,
			'Retrieved scrollbar width.'
		);

	} );

}() );

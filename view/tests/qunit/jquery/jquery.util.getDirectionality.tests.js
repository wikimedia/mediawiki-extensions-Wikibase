/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function () {
	'use strict';

	QUnit.module( 'jquery.util.getDirectionality' );

	QUnit.test( 'Basic tests', function ( assert ) {
		if ( $.uls && $.uls.data ) {
			assert.strictEqual(
				$.util.getDirectionality( 'fa' ),
				'rtl',
				'Retrieved language code from ULS.'
			);

			// There is no reason to further test ULS behaviour as ULS is supposed to always return a
			// sensible directionality string.
		} else {
			assert.strictEqual(
				$.util.getDirectionality( 'doesNotExist' ),
				'auto',
				'Falling back to "auto"'
			);
		}
	} );

}() );

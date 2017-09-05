/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( $, QUnit ) {
	'use strict';

	QUnit.module( 'jquery.util.getDirectionality' );

	QUnit.test( 'Basic tests', function ( assert ) {
		if ( $.uls && $.uls.data ) {
			assert.equal(
				$.util.getDirectionality( 'fa' ),
				'rtl',
				'Retrieved language code from ULS.'
			);

			// There is no reason to further test ULS behaviour as ULS is supposed to always return a
			// sensible directionality string.
			return;
		}

		assert.equal(
			$.util.getDirectionality( 'doesNotExist' ),
			'auto',
			'Falling back to "auto"'
		);
	} );

}( jQuery, QUnit ) );

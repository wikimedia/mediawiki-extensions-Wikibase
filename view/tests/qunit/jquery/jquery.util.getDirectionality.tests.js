/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, QUnit ) {
	'use strict';

var htmlDir = null;

QUnit.module( 'jquery.util.getDirectionality', {
	setup: function() {
		htmlDir = $( 'html' ).prop( 'dir' );
	},
	teardown: function() {
		$( 'html' ).prop( 'dir', htmlDir );
	}
} );

QUnit.test( 'Basic tests', function( assert ) {
	assert.expect( $.uls && $.uls.data ? 1 : 3 );
	var $html = $( 'html' );

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

	$html.prop( 'dir', 'ltr' );

	assert.equal(
		$.util.getDirectionality( 'doesNotExist' ),
		'ltr',
		'Falling back to HTML "dir" attribute.'
	);

	$html.prop( 'dir', 'rtl' );

	assert.equal(
		$.util.getDirectionality( 'doesNotExist' ),
		'rtl',
		'Verified falling back to HTML "dir" attribute after changing "dir" attribute.'
	);

	$html.removeAttr( 'dir' );

	assert.equal(
		$.util.getDirectionality( 'doesNotExist' ),
		'auto',
		'Falling back to HTML "auto" attribute if no "dir" attribute is set on the HTML element.'
	);
} );

}( jQuery, QUnit ) );

/**
 * Browser feature detection routines.
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

	/**
	 * Returns an object holding information about whether certain features are supported in the
	 * user's browser.
	 */
	wb.support = ( function() {
		var support = {};

		// Checking instanceof in IE8 will fail after having performed a deep extend on an object
		// containing a constructor with a custom prototype.
		support.constructorDeepExtend = true;
		var test = function() { this.foo = ''; };
		test.prototype = { bar: '' };

		var derivate = new test(),
			extended = $.extend( true, {}, { member: derivate } );

		support.constructorDeepExtend = ( extended.member instanceof test );

		return support;
	} )();

} )( mediaWiki, wikibase, jQuery );

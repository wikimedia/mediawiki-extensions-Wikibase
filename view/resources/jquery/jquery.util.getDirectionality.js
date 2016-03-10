( function ( $ ) {
'use strict';

$.util = $.util || {};

/**
 * Returns the directionality of a language by querying the Universal Language Selector. If ULS is
 * not available the HTML element's `dir` attribute is evaluated. If that is unset, `auto` is
 * returned.
 *
 * @method jQuery.util.getDirectionality
 * @member jQuery.util
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @param {string} languageCode
 * @return {string}
 */
$.util.getDirectionality = function( languageCode ) {
	var dir = $.uls && $.uls.data
		? $.uls.data.getDir( languageCode )
		: $( 'html' ).prop( 'dir' );

	return dir || 'auto';
};

}( jQuery ) );

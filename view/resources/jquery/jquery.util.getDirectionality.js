( function () {
	'use strict';

	$.util = $.util || {};

	/**
	 * Returns the directionality of a language by querying the Universal Language Selector.
	 * If ULS is not available `auto` is returned.
	 *
	 * @method jQuery.util.getDirectionality
	 * @member jQuery.util
	 * @license GPL-2.0-or-later
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @param {string} languageCode
	 * @return {string}
	 */
	$.util.getDirectionality = function ( languageCode ) {
		return $.uls && $.uls.data
			? $.uls.data.getDir( languageCode )
			: 'auto';
	};

}() );

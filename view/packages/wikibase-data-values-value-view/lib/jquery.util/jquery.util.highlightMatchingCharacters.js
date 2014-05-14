/**
 * "Highlights" matching characters at the start of a string using HTML.
 *
 * @example var highlighted = $.util.highlightMatchingCharacters( 'abc', 'abcdef' );
 *          highlighted === '<b>abc</b>def';
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @dependency jQuery
 */
jQuery.util = jQuery.util || {};

// TODO: Convert to jQuery plugin and provide option to allow highlighting within the string.
jQuery.util.highlightMatchingCharacters = ( function( $ ) {
	'use strict';

	/**
	 * Escapes a string to be used in a regular expression.
	 *
	 * @param {string} value
	 * @returns {string}
	 */
	function escapeRegex( value ) {
		return value.replace( /[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&' );
	}

	/**
	 * Wraps the part of a string that matches a substring inside a <b> tag if the string starts
	 * with the specified substring.
	 *
	 * @param {string} substring
	 * @param {string} string
	 * @param {boolean} caseInsensitive
	 * @return {string}
	 */
	return function( substring, string, caseInsensitive ) {
		if( substring === '' || string === '' ) {
			return string;
		}

		var escapedSubstring = escapeRegex( substring ),
			regExp = new RegExp(
				'((?:(?!' + escapedSubstring +').)*?)(' + escapedSubstring + ')(.*)',
				caseInsensitive ? 'i' : ''
			);

		if(
			string.indexOf( substring ) === 0
			|| caseInsensitive && string.toLowerCase().indexOf( substring.toLowerCase )
		) {
			var matches = string.match( regExp );
			string = matches[1] + '<b>' + matches[2] + '</b>' + matches[3];
		}
		return string;
	};

} )( jQuery );

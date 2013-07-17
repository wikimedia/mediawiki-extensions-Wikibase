/**
 * adaptlettercase helper function
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @dependency jQuery
 */
jQuery.util = jQuery.util || {};

jQuery.util.adaptlettercase = ( function( $ ) {
	'use strict';

	/**
	 * Adapts the letter case of a source string to a destination string. The destination string is
	 * supposed to consist our of the source string's first letter(s).
	 *
	 * @param {string} source
	 * @param {string} destination
	 * @param {string|undefined} method "all" will adapt source's letter case for all destination
	 *        characters, "first" will adapt the first letter only. By default, no adaption is
	 *        taking place.
	 * @return {string}
	 *
	 * @throws {Error} if source and/or destination string is not specified.
	 * @throws {Error} if source string does not start with destination string.
	 */
	return function( source, destination, method ) {
		if( !source || !destination ) {
			throw new Error( 'Source and destination need to be specified.' );
		}

		if( source.toLowerCase().indexOf( destination.toLowerCase() ) === -1 ) {
			throw new Error( source + ' does not start with ' + destination + '.' );
		}

		if ( method === 'all' ) {
			return destination.substr( 0, source.length );
		} else if ( method === 'first' ) {
			return destination.substr( 0, 1 ) + source.substr( 1 );
		} else {
			return source;
		}
	};

} )( jQuery );

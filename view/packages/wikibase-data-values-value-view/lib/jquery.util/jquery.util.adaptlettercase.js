jQuery.util = jQuery.util || {};

jQuery.util.adaptlettercase = ( function() {
	'use strict';

	/**
	 * Applies the letter case of a source string to a destination string. The destination string's
	 * character sequence is supposed to mirror the source string's first (or all) characters
	 * (although the characters may differ in their letter-case of course).
	 *
	 * @member jQuery.util
	 * @method adaptlettercase
	 * @license GNU GPL v2+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @param {string} destination String the source string's letter-case shall be applied to.
	 * @param {string} source String whose letter-case shall be applied to destination.
	 * @param {string} [method] "all" will adapt source's letter case for all destination characters
	 *        "first" will adapt the first letter only. By default, no adaption is taking place.
	 * @return {string}
	 *
	 * @throws {Error} if destination and/or source string is/are not specified.
	 */
	return function( destination, source, method ) {
		if ( !destination || !source ) {
			throw new Error( 'Destination and source need to be specified.' );
		}

		if ( source.toLowerCase().indexOf( destination.toLowerCase() ) !== 0 ) {
			return destination;
		}

		if ( method === 'all' ) {
			return source.substr( 0, destination.length );
		} else if ( method === 'first' ) {
			return source.substr( 0, 1 ) + destination.substr( 1 );
		} else {
			return destination;
		}
	};

} )();

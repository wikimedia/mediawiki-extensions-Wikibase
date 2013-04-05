/**
 * Globally Unique IDentifier generator.
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
 * Generator for a Globally Unique IDentifier.
 * @abstract
 * @constructor
 * @since 0.4
 */
wb.utilities.GuidGenerator = function GuidGenerator(){};
$.extend( wb.utilities.GuidGenerator.prototype, {
	/**
	 * Generates and returns a Globally Unique IDentifier.
	 * @since 0.4
	 *
	 * @return {string}
	 */
	newGuid: wb.utilities.abstractMember
} );

/**
 * Generates and returns a GUID.
 * @see http://php.net/manual/en/function.com-create-guid.php
 * @return {string}
 */
wb.utilities.V4GuidGenerator = wb.utilities.inherit(
	'V4GuidGenerator',
	wb.utilities.GuidGenerator, {
		/**
		 * Returns a random hexadecimal number in a given range of integers.
		 * (see PHP implementation)
		 *
		 * @param {number} min Minimum number
		 * @param {number} max Maximum number
		 * @return {Number}
		 */
		_getRandomHex: function( min, max ) {
			return ( Math.floor( Math.random() * ( max - min + 1 ) ) + min ).toString( 16 );
		},

		/**
		 * @see wb.utilities.GuidGenerator
		 */
		newGuid: function() {
			var self = this,
				template = 'xx-x-x-x-xxx',
				guid = '';

			$.each( template, function( i, character ) {
				if ( character === '-' ) {
					guid += '-';
					return true;
				}

				var hex;
				if ( i === 3 ) {
					hex = self._getRandomHex( 16384, 20479 );
				} else if ( i === 4 ) {
					hex = self._getRandomHex( 32768, 49151 );
				} else {
					hex = self._getRandomHex( 0, 65535 );
				}

				while ( hex.length < 4 ) {
					hex = '0' +  hex;
				}

				guid += hex;

			} );

			return guid;
		}
	}
);

} )( mediaWiki, wikibase, jQuery );

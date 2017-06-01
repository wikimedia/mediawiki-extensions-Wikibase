/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( wb, util, $ ) {
	'use strict';

	/**
	 * Generator for a Globally Unique IDentifier.
	 *
	 * @abstract
	 * @constructor
	 */
	wb.utilities.GuidGenerator = function GuidGenerator() {
	};

	$.extend( wb.utilities.GuidGenerator.prototype, {
		/**
		 * Generates and returns a Globally Unique IDentifier.
		 *
		 * @return {string}
		 */
		newGuid: util.abstractMember
	} );

	/**
	 * Generates and returns a GUID.
	 * @see http://php.net/manual/en/function.com-create-guid.php
	 * @return {string}
	 */
	wb.utilities.V4GuidGenerator = util.inherit(
		'V4GuidGenerator',
		wb.utilities.GuidGenerator, {
			/**
			 * Returns a random hexadecimal number in a given range of integers.
			 * (see PHP implementation)
			 *
			 * @param {number} min Minimum number
			 * @param {number} max Maximum number
			 * @return {string}
			 */
			_getRandomHex: function ( min, max ) {
				return ( Math.floor( Math.random() * ( max - min + 1 ) ) + min ).toString( 16 );
			},

			/**
			 * @see wb.utilities.GuidGenerator
			 */
			newGuid: function () {
				var self = this,
					template = 'xx-x-x-x-xxx',
					guid = '';

				for ( var i = 0; i < template.length; i++ ) {
					var character = template.charAt( i );

					if ( character === '-' ) {
						guid += '-';
						continue;
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
						hex = '0' + hex;
					}

					guid += hex;
				}

				return guid;
			}
		}
	);

}( wikibase, util, jQuery ) );

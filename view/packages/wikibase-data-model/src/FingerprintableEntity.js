( function() {
	'use strict';

var Entity = require( './Entity.js' ),
	PARENT = Entity;

/**
* Abstract FingerprintableEntity class featuring an id and a fingerprint.
* @class FingerprintableEntity
* @abstract
* @since 4.1.0
* @license GPL-2.0+
* @author T. Arrow < thomas.arrow_ext@wikimedia.de >
*
* @constructor
*
* @throws {Error} when trying to instantiate since FingerprintableEntity is abstract.
*/

module.exports = util.inherit(
	'WbDataModelFingerprintableEntity',
	PARENT,
	{
		/**
		 * @property {Fingerprint}
		 * @private
		 */
		_fingerprint: null,

		/**
		 * @return {Fingerprint}
		 */
		getFingerprint: function() {
			return this._fingerprint;
		},

		/**
		 * @param {Fingerprint} fingerprint
		 */
		setFingerprint: function( fingerprint ) {
			this._fingerprint = fingerprint;
		}
	}
);

}() );

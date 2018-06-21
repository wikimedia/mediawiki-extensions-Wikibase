( function( wb, $ ) {
	'use strict';

var PARENT = wb.datamodel.Entity;

/**
* Abstract FingerprintableEntity class featuring an id and a fingerprint.
* @class wikibase.datamodel.FingerprintableEntity
* @abstract
* @since 4.1.0
* @license GPL-2.0+
* @author T. Arrow < thomas.arrow_ext@wikimedia.de >
*
* @constructor
*
* @throws {Error} when trying to instantiate since FingerprintableEntity is abstract.
*/

var SELF = wb.datamodel.FingerprintableEntity = util.inherit(
	'WbDataModelFingerprintableEntity',
	PARENT,
	{
		/**
		 * @property {wikibase.datamodel.Fingerprint}
		 * @private
		 */
		_fingerprint: null,

		/**
		 * @return {wikibase.datamodel.Fingerprint}
		 */
		getFingerprint: function() {
			return this._fingerprint;
		},

		/**
		 * @param {wikibase.datamodel.Fingerprint} fingerprint
		 */
		setFingerprint: function( fingerprint ) {
			this._fingerprint = fingerprint;
		}
	}
);

}( wikibase, jQuery ) );

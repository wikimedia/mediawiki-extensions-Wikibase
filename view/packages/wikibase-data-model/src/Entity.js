( function( wb, $ ) {
	'use strict';

/**
 * Abstract Entity base class featuring an id and a fingerprint.
 * @class wikibase.datamodel.Entity
 * @abstract
 * @since 0.3
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @throws {Error} when trying to instantiate since Entity is abstract.
 */
var SELF = wb.datamodel.Entity = function WbDataModelEntity() {
	throw new Error( 'Cannot construct abstract Entity object' );
};

/**
 * String to identify this type of Entity.
 * @property {string} [TYPE=null]
 * @static
 */
SELF.TYPE = null;

$.extend( SELF.prototype, {
	/**
	 * @property {string}
	 * @private
	 */
	_id: null,

	/**
	 * @property {wikibase.datamodel.Fingerprint}
	 * @private
	 */
	_fingerprint: null,

	/**
	 * @return {string}
	 */
	getId: function() {
		return this._id;
	},

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
	},

	/**
	 * Returns what type of Entity this is.
	 *
	 * @return string
	 */
	getType: function() {
		return this.constructor.TYPE;
	},

	/**
	 * @abstract
	 *
	 * @return {boolean}
	 */
	isEmpty: util.abstractMember,

	/**
	 * @abstract
	 *
	 * @param {*} entity
	 * @return {boolean}
	 */
	equals: util.abstractMember
} );

}( wikibase, jQuery ) );

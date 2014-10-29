/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
	'use strict';

/**
 * @constructor
 * @abstract
 * @since 0.3
 */
var SELF = wb.datamodel.Entity = function WbDataModelEntity() {
	throw new Error( 'Cannot construct abstract Entity object' );
};

/**
 * String to identify this type of Entity.
 * @type {string}
 */
SELF.TYPE = null;

$.extend( SELF.prototype, {
	/**
	 * @type {string}
	 */
	_id: null,

	/**
	 * @type {wikibase.datamodel.Fingerprint}
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
	 * @return {boolean}
	 */
	isEmpty: util.abstractMember,

	/**
	 * @param {*}
	 * @return {boolean}
	 */
	equals: util.abstractMember
} );

}( wikibase, jQuery ) );

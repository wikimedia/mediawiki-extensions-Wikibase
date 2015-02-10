/**
 * Globally Unique IDentifier generator for claims.
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
	'use strict';

/**
 * Claim GUID generator.
 * @since 0.4
 * @param {string} entityId Prefixed entity id
 */
wb.utilities.ClaimGuidGenerator = function ClaimGuidGenerator( entityId ) {
	this._baseGenerator = new wb.utilities.V4GuidGenerator();
	this._entityId = entityId;
};

$.extend( wb.utilities.ClaimGuidGenerator.prototype, {
	/**
	 * @property {wikibase.utilities.V4GuidGenerator}
	 */
	_baseGenerator: null,

	/**
	 * @property {string}
	 */
	_entityId: null,

	/**
	 * Returns a new GUID for the entity id specified in the constructor.
	 *
	 * @returns {string} GUID
	 */
	newGuid: function() {
		return this._entityId + '$' + this._baseGenerator.newGuid();
	}
} );

}( wikibase, jQuery ) );

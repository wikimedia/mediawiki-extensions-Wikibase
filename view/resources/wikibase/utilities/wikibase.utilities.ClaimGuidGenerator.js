/**
 * Globally Unique IDentifier generator for claims.
 *
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( wb ) {
	'use strict';

	var V4GuidGenerator = require( './wikibase.utilities.GuidGenerator.js' );

	wb.utilities = wb.utilities || {};

	/**
	 * Claim GUID generator.
	 *
	 * @param {string} entityId Prefixed entity id
	 */
	wb.utilities.ClaimGuidGenerator = function ClaimGuidGenerator( entityId ) {
		this._baseGenerator = new V4GuidGenerator();
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
		 * @return {string} GUID
		 */
		newGuid: function () {
			return this._entityId + '$' + this._baseGenerator.newGuid();
		}
	} );

}( wikibase ) );

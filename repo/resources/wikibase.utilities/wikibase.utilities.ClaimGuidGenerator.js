/**
 * Globally Unique IDentifier generator for claims.
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
 * Claim GUID generator.
 * @since 0.4
 */
wb.utilities.ClaimGuidGenerator = function ClaimGuidGenerator() {
	this._baseGenerator = new wb.utilities.V4GuidGenerator();
};

$.extend( wb.utilities.ClaimGuidGenerator.prototype, {
	/**
	 * Returns a new GUID for the specified entity id.
	 *
	 * @param {string} entityId Prefixed entity id
	 * @returns {string} GUID
	 */
	newGuid: function( entityId ) {
		return entityId + '$' + this._baseGenerator.newGuid();
	}
} );

}( mediaWiki, wikibase, jQuery ) );

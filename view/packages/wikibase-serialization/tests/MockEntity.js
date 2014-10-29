/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
'use strict';

wb.serialization.tests = wb.serialization.tests || {};

/**
 * @param {string} id
 * @param {wikibase.datamodel.Fingerprint} fingerprint
 * @constructor
 * @extends {wikibase.datamodel.Entity}
 */
var SELF = wb.serialization.tests.MockEntity = util.inherit(
	'wbMockEntity',
	wb.datamodel.Entity,
	function WbMockEntity( id, fingerprint ) {
		this._id = id;
		this._fingerprint = fingerprint;
	},
{
	/**
	 * @see wikibase.datamodel.Entity
	 */
	isEmpty: function() {
		return this._fingerprint.isEmpty();
	},

	/**
	 * @see wikibase.datamodel.Entity
	 */
	equals: function( mock ) {
		return this._id === mock.getId() && this._fingerprint.equals( mock.getFingerprint() );
	}
} );

SELF.TYPE = 'mock';

}( wikibase, util ) );

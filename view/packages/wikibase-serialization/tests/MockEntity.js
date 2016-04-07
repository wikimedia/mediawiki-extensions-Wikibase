/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
'use strict';

wb.serialization.tests = wb.serialization.tests || {};

/**
 * @extends wikibase.datamodel.Entity
 *
 * @constructor
 *
 * @param {string} id
 * @param {wikibase.datamodel.Fingerprint} fingerprint
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
	 * @inheritdoc
	 */
	isEmpty: function() {
		return this._fingerprint.isEmpty();
	},

	/**
	 * @inheritdoc
	 */
	equals: function( mock ) {
		return this._id === mock.getId() && this._fingerprint.equals( mock.getFingerprint() );
	}
} );

SELF.TYPE = 'mock';

}( wikibase, util ) );

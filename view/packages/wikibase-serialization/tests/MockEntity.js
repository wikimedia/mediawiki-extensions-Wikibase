/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function() {
	'use strict';

	var datamodel = require( 'wikibase.datamodel' );

	/**
 * @extends datamodel.Entity
 *
 * @constructor
 *
 * @param {string} id
 * @param {datamodel.Fingerprint} fingerprint
 */
	var SELF = util.inherit(
		'wbMockEntity',
		datamodel.FingerprintableEntity,
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

	module.exports = SELF;
}() );

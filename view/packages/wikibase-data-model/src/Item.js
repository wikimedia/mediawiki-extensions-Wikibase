( function( util ) {
	'use strict';

var FingerprintableEntity = require( './FingerprintableEntity.js' ),
	Fingerprint = require( './Fingerprint.js' ),
	StatementGroupSet = require( './StatementGroupSet.js' ),
	SiteLinkSet = require( './SiteLinkSet.js' ),
	PARENT = FingerprintableEntity;

/**
 * Entity derivative featuring statements and site links.
 * @class Item
 * @extends FingerprintableEntity
 * @since 1.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {string} entityId
 * @param {Fingerprint|null} [fingerprint=new Fingerprint()]
 * @param {StatementGroupSet|null} [statementGroupSet=new StatementGroupSet()]
 * @param {SiteLinkSet|null} [siteLinkSet=new SiteLinkSet()]
 *
 * @throws {Error} if a required parameter is not specified properly.
 */
var SELF = util.inherit(
	'WbDataModelItem',
	PARENT,
	function( entityId, fingerprint, statementGroupSet, siteLinkSet ) {
		fingerprint = fingerprint || new Fingerprint();
		statementGroupSet = statementGroupSet || new StatementGroupSet();
		siteLinkSet = siteLinkSet || new SiteLinkSet();

		if(
			typeof entityId !== 'string'
			|| !( fingerprint instanceof Fingerprint )
			|| !( siteLinkSet instanceof SiteLinkSet )
			|| !( statementGroupSet instanceof StatementGroupSet )
		) {
			throw new Error( 'Required parameter(s) missing or not defined properly' );
		}

		this._id = entityId;
		this._fingerprint = fingerprint;
		this._siteLinkSet = siteLinkSet;
		this._statementGroupSet = statementGroupSet;
	},
{
	/**
	 * @property {SiteLinkSet}
	 * @private
	 */
	_siteLinkSet: null,

	/**
	 * @property {StatementGroupSet}
	 * @private
	 */
	_statementGroupSet: null,

	/**
	 * @return {SiteLinkSet}
	 */
	getSiteLinks: function() {
		return this._siteLinkSet;
	},

	/**
	 * @param {SiteLink} siteLink
	 */
	removeSiteLink: function( siteLink ) {
		this._siteLinkSet.removeSiteLink( siteLink );
	},

	/**
	 * @return {StatementGroupSet}
	 */
	getStatements: function() {
		return this._statementGroupSet;
	},

	/**
	 * @return {boolean}
	 */
	isEmpty: function() {
		return this._fingerprint.isEmpty()
			&& this._siteLinkSet.isEmpty()
			&& this._statementGroupSet.isEmpty();
	},

	/**
	 * @param {*} item
	 * @return {boolean}
	 */
	equals: function( item ) {
		return item === this
			|| item instanceof SELF
				&& this._id === item.getId()
				&& this._fingerprint.equals( item.getFingerprint() )
				&& this._siteLinkSet.equals( item.getSiteLinks() )
				&& this._statementGroupSet.equals( item.getStatements() );
	}
} );

/**
 * @inheritdoc
 * @property {string} [TYPE='item']
 * @static
 */
SELF.TYPE = 'item';

module.exports = SELF;

}( util ) );

( function( wb, util ) {
	'use strict';

var PARENT = wb.datamodel.FingerprintableEntity;

/**
 * Entity derivative featuring statements and site links.
 * @class wikibase.datamodel.Item
 * @extends wikibase.datamodel.FingerprintableEntity
 * @since 1.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {string} entityId
 * @param {wikibase.datamodel.Fingerprint|null} [fingerprint=new wikibase.datamodel.Fingerprint()]
 * @param {wikibase.datamodel.StatementGroupSet|null} [statementGroupSet=new wikibase.datamodel.StatementGroupSet()]
 * @param {wikibase.datamodel.SiteLinkSet|null} [siteLinkSet=new wikibase.datamodel.SiteLinkSet()]
 *
 * @throws {Error} if a required parameter is not specified properly.
 */
var SELF = wb.datamodel.Item = util.inherit(
	'WbDataModelItem',
	PARENT,
	function( entityId, fingerprint, statementGroupSet, siteLinkSet ) {
		fingerprint = fingerprint || new wb.datamodel.Fingerprint();
		statementGroupSet = statementGroupSet || new wb.datamodel.StatementGroupSet();
		siteLinkSet = siteLinkSet || new wb.datamodel.SiteLinkSet();

		if(
			typeof entityId !== 'string'
			|| !( fingerprint instanceof wb.datamodel.Fingerprint )
			|| !( siteLinkSet instanceof wb.datamodel.SiteLinkSet )
			|| !( statementGroupSet instanceof wb.datamodel.StatementGroupSet )
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
	 * @property {wikibase.datamodel.SiteLinkSet}
	 * @private
	 */
	_siteLinkSet: null,

	/**
	 * @property {wikibase.datamodel.StatementGroupSet}
	 * @private
	 */
	_statementGroupSet: null,

	/**
	 * @return {wikibase.datamodel.SiteLinkSet}
	 */
	getSiteLinks: function() {
		return this._siteLinkSet;
	},

	/**
	 * @param {wikibase.datamodel.SiteLink} siteLink
	 */
	addSiteLink: function( siteLink ) {
		this._siteLinkSet.setSiteLink( siteLink );
	},

	/**
	 * @param {wikibase.datamodel.SiteLink} siteLink
	 */
	removeSiteLink: function( siteLink ) {
		this._siteLinkSet.removeSiteLink( siteLink );
	},

	/**
	 * @return {wikibase.datamodel.StatementGroupSet}
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

}( wikibase, util ) );

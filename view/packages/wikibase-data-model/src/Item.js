/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var PARENT = wb.datamodel.Entity;

/**
 * Represents a Wikibase Item.
 * @constructor
 * @extends wikibase.datamodel.Entity
 * @since 0.4
 *
 * @param {string} entityId
 * @param {wikibase.datamodel.Fingerprint|null} [fingerprint]
 * @param {wikibase.datamodel.StatementGroupList|null} [statementGroupList]
 * @param {wikibase.datamodel.SiteLinkList|null} [siteLinkList]
 */
var SELF = wb.datamodel.Item = util.inherit(
	'WbItem',
	PARENT,
	function( entityId, fingerprint, statementGroupList, siteLinkList ) {
		fingerprint = fingerprint || new wb.datamodel.Fingerprint();
		statementGroupList = statementGroupList || new wb.datamodel.StatementGroupList();
		siteLinkList = siteLinkList || new wb.datamodel.SiteLinkList();

		if(
			typeof entityId !== 'string'
			|| !( fingerprint instanceof wb.datamodel.Fingerprint )
			|| !( siteLinkList instanceof wb.datamodel.SiteLinkList )
			|| !( statementGroupList instanceof wb.datamodel.StatementGroupList )
		) {
			throw new Error( 'Required parameter(s) missing or not defined properly' );
		}

		this._id = entityId;
		this._fingerprint = fingerprint;
		this._siteLinkList = siteLinkList;
		this._statementGroupList = statementGroupList;
	},
{
	/**
	 * @type {wikibase.datamodel.SiteLinkList}
	 */
	_siteLinkList: null,

	/**
	 * @type {wikibase.datamodel.StatementGroupList}
	 */
	_statementGroupList: null,

	/**
	 * @return {wikibase.datamodel.SiteLinkList}
	 */
	getSiteLinks: function() {
		return this._siteLinkList;
	},

	/**
	 * @param {wikibase.datamodel.SiteLink} siteLink
	 */
	addSiteLink: function( siteLink ) {
		this._siteLinkList.setSiteLink( siteLink );
	},

	/**
	 * @param {wikibase.datamodel.SiteLink} siteLink
	 */
	removeSiteLink: function( siteLink ) {
		this._siteLinkList.removeSiteLink( siteLink );
	},

	/**
	 * @return {wikibase.datamodel.StatementGroupList}
	 */
	getStatements: function() {
		return this._statementGroupList;
	},

	/**
	 * @param {wikibase.datamodel.Statement} statement
	 */
	addStatement: function( statement ) {
		this._statementGroupList.addStatement( statement );
	},

	/**
	 * @param {wikibase.datamodel.Statement} statement
	 */
	removeStatement: function( statement ) {
		this._statementGroupList.removeStatement( statement );
	},

	/**
	 * @return {boolean}
	 */
	isEmpty: function() {
		return this._fingerprint.isEmpty()
			&& this._siteLinkList.isEmpty()
			&& this._statementGroupList.isEmpty();
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
				&& this._siteLinkList.equals( item.getSiteLinks() )
				&& this._statementGroupList.equals( item.getStatements() );
	}
} );

/**
 * @see wikibase.datamodel.Entity.TYPE
 */
SELF.TYPE = 'item';

}( wikibase, util ) );

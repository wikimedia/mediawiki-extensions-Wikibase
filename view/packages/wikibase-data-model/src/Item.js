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
 * @param {wikibase.datamodel.StatementList|null} [statementList]
 * @param {wikibase.datamodel.SiteLinkList|null} [siteLinkList]
 */
var SELF = wb.datamodel.Item = util.inherit(
	'WbItem',
	PARENT,
	function( entityId, fingerprint, statementList, siteLinkList ) {
		fingerprint = fingerprint || new wb.datamodel.Fingerprint();
		statementList = statementList || new wb.datamodel.StatementList();
		siteLinkList = siteLinkList || new wb.datamodel.SiteLinkList();

		if(
			typeof entityId !== 'string'
				|| !( fingerprint instanceof wb.datamodel.Fingerprint )
				|| !( siteLinkList instanceof wb.datamodel.SiteLinkList )
				|| !( statementList instanceof wb.datamodel.StatementList )
		) {
			throw new Error( 'Required parameter(s) missing or not defined properly' );
		}

		this._id = entityId;
		this._fingerprint = fingerprint || new wb.datamodel.Fingerprint();
		this._siteLinkList = siteLinkList || wb.datamodel.SiteLinkList();
		this._statementList = statementList || new wb.datamodel.StatementList();
	},
{
	/**
	 * @type {wikibase.datamodel.SiteLinkList}
	 */
	_siteLinkList: null,

	/**
	 * @type {wikibase.datamodel.StatementList}
	 */
	_statementList: null,

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
	 * @return {wikibase.datamodel.StatementList}
	 */
	getStatements: function() {
		return this._statementList;
	},

	/**
	 * @param {wikibase.datamodel.Statement} statement
	 */
	addStatement: function( statement ) {
		this._statementList.addStatement( statement );
	},

	/**
	 * @param {wikibase.datamodel.Statement} statement
	 */
	removeStatement: function( statement ) {
		this._statementList.removeStatement( statement );
	},

	/**
	 * @return {boolean}
	 */
	isEmpty: function() {
		return this._fingerprint.isEmpty()
			&& this._siteLinkList.isEmpty()
			&& this._statementList.isEmpty();
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
				&& this._statementList.equals( item.getStatements() );
	}
} );

/**
 * @see wikibase.datamodel.Entity.TYPE
 */
SELF.TYPE = 'item';

}( wikibase, util ) );

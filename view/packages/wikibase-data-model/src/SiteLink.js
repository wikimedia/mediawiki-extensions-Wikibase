/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
'use strict';

/**
 * Represents a site link.
 * @constructor
 * @since 0.3
 *
 * @param {string} siteId
 * @param {string} pageName
 * @param {string[]} [badges]
 *
 * @throws {Error} if required parameters are not specified.
 */
var SELF = wb.datamodel.SiteLink = function WbDataModelSiteLink( siteId, pageName, badges ) {
	if( siteId === undefined || pageName === undefined ) {
		throw new Error( 'Required parameters not specified' );
	}

	this._siteId = siteId;
	this._pageName = pageName;
	this._badges = badges || [];
};

$.extend( SELF.prototype, {
	/**
	 * @type {string}
	 */
	_siteId: null,

	/**
	 * @type {string|null}
	 */
	_pageName: null,

	/**
	 * @type {string[]}
	 */
	_badges: null,

	/**
	 * @return {string}
	 */
	getSiteId: function() {
		return this._siteId;
	},

	/**
	 * @return {string|null}
	 */
	getPageName: function() {
		return this._pageName;
	},

	/**
	 * @param {string[]} [badges]
	 */
	setBadges: function( badges ) {
		this._badges = badges || [];
	},

	/**
	 * @return {string[]}
	 */
	getBadges: function() {
		return this._badges;
	},

	/**
	 * @param {*} siteLink
	 * @return {boolean}
	 */
	equals: function( siteLink ) {
		if( !( siteLink instanceof SELF ) ) {
			return false;
		}

		var otherBadges = siteLink.getBadges();

		if(
			this._siteId !== siteLink.getSiteId()
			|| this._pageName !== siteLink.getPageName()
			|| this._badges.length !== otherBadges.length
		) {
			return false;
		}

		for( var i = 0; i < this._badges.length; i++ ) {
			if( $.inArray( this._badges[i], otherBadges ) === -1 ) {
				return false;
			}
		}

		return true;
	}

} );
}( wikibase, jQuery ) );

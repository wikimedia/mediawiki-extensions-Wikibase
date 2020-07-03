( function( $ ) {
'use strict';

/**
 * Combination of a site id, a page name and a list of badges.
 * @class SiteLink
 * @since 0.3
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {string} siteId
 * @param {string|null} pageName
 * @param {string[]} [badges=[]]
 *
 * @throws {Error} if a required parameter is not specified properly.
 */
var SELF = function WbDataModelSiteLink( siteId, pageName, badges ) {
	if( siteId === undefined || pageName === undefined ) {
		throw new Error( 'Required parameters not specified' );
	}

	this._siteId = siteId;
	this._pageName = pageName;
	this._badges = badges || [];
};

/**
 * @class SiteLink
 */
$.extend( SELF.prototype, {
	/**
	 * @property {string}
	 * @private
	 */
	_siteId: null,

	/**
	 * @property {string|null}
	 * @private
	 */
	_pageName: null,

	/**
	 * @property {string[]}
	 * @private
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
		if( siteLink === this ) {
			return true;
		} else if( !( siteLink instanceof SELF )
			|| this._siteId !== siteLink.getSiteId()
			|| this._pageName !== siteLink.getPageName()
		) {
			return false;
		}

		var otherBadges = siteLink.getBadges();

		if( this._badges.length !== otherBadges.length ) {
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

module.exports = SELF;

}( jQuery ) );

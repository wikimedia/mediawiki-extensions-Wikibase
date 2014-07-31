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
 * @param {wikibase.datamodel.Site} site
 * @param {string} pageName
 * @param {string[]} badges
 *
 * @throws {Error} if no Site object is specified as parameter.
 */
var SELF = wb.datamodel.SiteLink = function WbDataModelSiteLink( site, pageName, badges ) {
	if( site === undefined ) {
		throw new Error( 'Site needs to be specified' );
	}

	this._site = site;
	this._pageName = pageName || null;
	this._badges = badges || [];
};

$.extend( SELF.prototype, {
	/**
	 * @type {wikibase.Site}
	 */
	_site: null,

	/**
	 * @type {string|null}
	 */
	_pageName: null,

	/**
	 * @type {string[]}
	 */
	_badges: null,

	/**
	 * @return {wikibase.Site}
	 */
	getSite: function() {
		return this._site;
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
	 * Returns the url to a page of the site.
	 *
	 * @return {string}
	 */
	getUrl: function() {
		var pageName = this._urlEncode( $.trim( this._pageName ) );
		return this._site.getPageUrl().replace( /\$1/g, pageName );
	},

	/**
	 * Returns a html link to a site of the site.
	 *
	 * @return {jQuery}
	 */
	getLink: function() {
		var url = this.getUrl();
		return $( '<a/>', {
			'href': url,
			'text': this._pageName
		} );
	},

	/**
	 * @param {string} string
	 * @return {string}
	 */
	_urlEncode: function( string ) {
		return encodeURIComponent( string );
	}

} );
}( wikibase, jQuery ) );

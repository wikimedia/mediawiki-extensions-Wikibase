/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
'use strict';

/**
 * Unordered set of SiteLink objects.
 * @constructor
 * @since 0.4
 *
 * @param {wikibase.datamodel.SiteLink[]} siteLinks
 */
var SELF = wb.datamodel.SiteLinkList = function WbDataModelSiteLinkList( siteLinks ) {
	siteLinks = siteLinks || [];

	this._siteLinks = {};
	this.length = 0;

	for( var i = 0; i < siteLinks.length; i++ ) {
		if( !siteLinks[i] instanceof wb.datamodel.SiteLink ) {
			throw new Error( 'SiteLinkList may contain SiteLink instances only' );
		}

		var siteId = siteLinks[i].getSiteId();

		if( this._siteLinks[siteId] ) {
			throw new Error( 'There may only be one SiteLink per site id' );
		}

		this.setSiteLink( siteLinks[i] );
	}
};

$.extend( SELF.prototype, {
	/**
	 * @type {Object}
	 */
	_siteLinks: null,

	/**
	 * @type {number}
	 */
	length: 0,

	/**
	 * @param {string} siteId
	 * @return {wikibase.datamodel.SiteLink|null}
	 */
	getBySiteId: function( siteId ) {
		return this._siteLinks[siteId] || null;
	},

	/**
	 * @param {string} siteId
	 */
	removeBySiteId: function( siteId ) {
		if( this._siteLinks[siteId] ) {
			this.length--;
		}
		delete this._siteLinks[siteId];
	},

	/**
	 * @param {wikibase.datamodel.SiteLink} siteLink
	 * @return {boolean}
	 */
	hasSiteLink: function( siteLink ) {
		var siteId = siteLink.getSiteId();
		return this._siteLinks[siteId] && this._siteLinks[siteId].equals( siteLink );
	},

	/**
	 * @param {wikibase.datamodel.SiteLink} siteLink
	 */
	setSiteLink: function( siteLink ) {
		var siteId = siteLink.getSiteId();

		if( !this._siteLinks[siteId] ) {
			this.length++;
		}

		this._siteLinks[siteId] = siteLink;
	},

	/**
	 * @param {*} siteLinkList
	 * @return {boolean}
	 */
	equals: function( siteLinkList ) {
		if( !( siteLinkList instanceof SELF ) ) {
			return false;
		}

		if( this.length !== siteLinkList.length ) {
			return false;
		}

		for( var siteId in this._siteLinks ) {
			if( !siteLinkList.hasSiteLink( this._siteLinks[siteId] ) ) {
				return false;
			}
		}

		return true;
	}

} );

}( wikibase, jQuery ) );

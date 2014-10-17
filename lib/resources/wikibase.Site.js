/**
 * JavaScript giving information about a site of the 'Wikibase' extension.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( mw, wb, $ ) {
'use strict';

/**
 * Offers information about a site known to the local Wikibase installation.
 * @constructor
 * @since 0.1
 */
var SELF = wb.Site = function WbSite( siteDetails ) {
	// TODO: Validate site details, throw error if invalid.
	// TODO: Keep copy of details, no reference.
	this._siteDetails = siteDetails;
};
$.extend( SELF.prototype, {
	/**
	 * Returns the sites id.
	 *
	 * @since 0.4 (
	 */
	getId: function() {
		return this._siteDetails.id;
	},

	/**
	 * Returns the full name of the site. This will return the name in the users language.
	 *
	 * @return string
	 */
	getName: function() {
		return this._siteDetails.name;
	},

	/**
	 * Returns the short name of the site. This will return the name in the users language.
	 *
	 * @return string
	 */
	getShortName: function() {
		return this._siteDetails.shortName;
	},

	/**
	 * Returns the group of the site.
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	getGroup: function() {
		return this._siteDetails.group;
	},

	/**
	 * Returns the link to the API
	 *
	 * @note: this might not be necessary later since we might want to do only API call to the
	 *        base wiki which will handle the calls to the sites.
	 *
	 * @return string
	 */
	getApi: function() {
		return this._siteDetails.apiUrl;
	},

	/**
	 * Returns the url to a page of the site. To get a full html ready link, use getLinkTo().
	 *
	 * @todo decide whether we want to stick with this method which relies on having some knowledge about the php
	 *       Site stuff (e.g. that we have to replace $1 in this._siteDetails.pageUrl) or whether we want to replace
	 *       this with a API call to the foreign site (even in that case we have to know which API module to call and
	 *       what parameters to pass in case we have a non-MW installation!)
	 *
	 * NOTE: for solving bug 40399 there is some additional magic in EditableSiteLink where we overwrite this function
	 *       to cache the exact urls returned by the API after creating a new site-link.
	 *
	 * @param {string} pageTitle title of the page within the site
	 * @return string
	 */
	getUrlTo: function( pageTitle ) {
		pageTitle = this._urlEncodeSite( $.trim( pageTitle ) );
		return this._siteDetails.pageUrl.replace( /\$1/g, pageTitle );
	},

	/**
	 * Returns a html link to a site of the site. To get the url only, use getUrlTo().
	 *
	 * @param {string} pageTitle title of the site within the site
	 * @return jQuery link to the site
	 */
	getLinkTo: function( pageTitle ) {
		var url = this.getUrlTo( pageTitle );
		return $( '<a>', {
			'href': url,
			'text': pageTitle
		} );
	},

	/**
	 * Returns the site's language code.
	 *
	 * @return string language code
	 */
	getLanguageCode: function() {
		return this._siteDetails.languageCode;
	},

	/**
	 * Returns the site language's text direction.
	 *
	 * @return {string}
	 */
	getLanguageDirection: function() {
		var languageCode = this.getLanguageCode();
		return $.uls && $.uls.data.languages[languageCode]
			? $.uls.data.getDir( languageCode )
			: 'auto';
	},

	/**
	 * Does the encoding for a site so it can be used within the url to the site.
	 * This should propably be over written in case the site is not a MediaWiki installation.
	 *
	 * @param {string} pageTitle
	 * @return string
	 */
	_urlEncodeSite: function( pageTitle ) {
		// we don't create a mw.Title here since the given title should be normalized and could be one from a foreign
		// wiki which has different namespace config!
		return mw.util.wikiUrlencode( pageTitle );
	}

} );
}( mediaWiki, wikibase, jQuery ) );

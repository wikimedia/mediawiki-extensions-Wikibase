/**
 * JavaScript giving information about a site of the 'Wikibase' extension.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
"use strict";

/**
 * Offers information about a site known to the local Wikibase installation.
 */
window.wikibase.Site = function( siteDetails ) {
	this._siteDetails = siteDetails;
};
window.wikibase.Site.prototype = {

	/**
	 * Returns the sites id.
	 */
	getId: function() {
		return this._siteDetails.id;
	},

	/**
	 * Returns the full name of the site. This will return the name in the users language.
	 *
	 * @param string
	 */
	getName: function() {
		return this._siteDetails.name;
	},

	/**
	 * Returns the short name of the site. This will return the name in the users language.
	 *
	 * @param string
	 */
	getShortName: function() {
		return this._siteDetails.shortName;
	},

	/**
	 * Returns the global site id of the site.
	 *
	 * @param string
	 */
	getGlobalSiteId: function() {
		return this._siteDetails.globalSiteId;
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
	 * @param string pageTitle title of the page within the site
	 * @return string
	 */
	getUrlTo: function( pageTitle ) {
		pageTitle = this._urlEncodeSite( $.trim( pageTitle ) );
		return this._siteDetails.pageUrl.replace( /\$1/g, pageTitle );
	},

	/**
	 * Returns a html link to a site of the site. To get the url only, use getUrlTo().
	 *
	 * @param string pageTitle title of the site within the site
	 * @return jQuery link to the site
	 */
	getLinkTo: function( pageTitle ) {
		var url = this.getUrlTo( pageTitle );
		return $( '<a/>', {
			'href': url,
			'text': pageTitle
		} );
	},

	/**
	 * Does the encoding for a site so it can be used within the url to the site.
	 * This should propably be over written in case the site is not a MediaWiki installation.
	 *
	 * @param string pageTitle
	 * @return string
	 */
	_urlEncodeSite: function( pageTitle ) {
		var mwPage = new mw.Title( pageTitle );
		return mw.util.wikiUrlencode( mwPage.getPrefixedDb() );
	}

};

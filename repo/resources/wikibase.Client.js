/**
 * JavasSript giving information about a client of the 'Wikibase' extension.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.sites.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
"use strict";

/**
 * Offers information about a client of the local Wikibase installation.
 */
window.wikibase.Client = function( siteDetails ) {
	this._siteDetails = siteDetails;
}
window.wikibase.Client.prototype = {
	
	/**
	 * Returns the full name of the client. This will return the name in the users language.
	 * 
	 * @param string
	 */
	getName: function() {
		return this._siteDetails.name;
	},
	
	/**
	 * Returns the short name of the client. This will return the name in the users language.
	 * 
	 * @param string
	 */
	getShortName: function() {
		return this._siteDetails.shortName;
	},
	
	/**
	 * Returns the link to the API
	 * 
	 * @note: this might not be necessary later since we might want to do only API call to the
	 *        base wiki which will handle the calls to the clients.
	 *        
	 * @return string
	 */
	getApi: function() {
		return this._siteDetails.apiUrl;
	},
	
	/**
	 * Returns the link to a site of the client.
	 * 
	 * @param string site title of the site within the client
	 * @return string
	 */
	getLinkTo: function( site ) {
		site = this._urlEncodeSite( $.trim( site ) );
		return this._siteDetails.pageUrl.replace( /\$1/g, site )
	},
	
	/**
	 * Does the encoding for a site so it can be used within the url to the site.
	 * This should propably be over written in case the client is not a MediaWiki installation.
	 * 
	 * @param siteTitle string
	 * @return string
	 */
	_urlEncodeSite: function( siteTitle ) {
		var mwSite = new mw.Title( siteTitle );
		return mw.util.wikiUrlencode( mwSite.getPrefixedDb() );
	}

};

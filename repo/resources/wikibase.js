/**
 * JavasSript for 'wikibase' extension
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner at wikimedia.de >
 */
"use strict";
window.wikibase = new( function() {
	
	/**
	 * Returns whether the Wikibase installation knows a client (site) with a certain ID.
	 * 
	 * @return bool
	 */
	this.hasClient = function ( siteId ) {
		return this.getClient( siteId ) !== null;
	};
	
	/**
	 * Returns an Client object with details about a client by the clients site ID. If there is no site
	 * related to the given ID, null will be returned.
	 * 
	 * @return wikibase.Client|null
	 */
	this.getClient = function( clientId ) {
		var client = mw.config.get('wbSiteDetails')[ clientId ];
		if( typeof clientId == 'undefined' ) {
			return null;
		}
		return new window.wikibase.Client( client );
	}
	
} )();

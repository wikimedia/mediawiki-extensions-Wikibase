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
	 * Will hold a list of all the clients after getClients() was called. This will cache the result.
	 * @var wikibase.Client[]
	 */
	this._clientList = null;
	
	/**
	 * Returns an array with all the known clients.
	 * 
	 * @return wikibase.Client[]
	 */
	this.getClients = function() {
		if( this._clientList !== null ) {
			// get cached list since this can be an expensive job to do
			return this._clientList;
		}
		
		var clientsDetails = mw.config.get('wbSiteDetails');
		this._clientList = {};
		
		for( var clientId in clientsDetails ) {
			var client = clientsDetails[ clientId ];
			client.id = clientId;
			this._clientList[ clientId ] =  new window.wikibase.Client( client );
		}
		
		return this._clientList;
	}
	
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
		var clients = this.getClients();
		var client = clients[ clientId ];
		
		if( typeof client == 'undefined' ) {
			return null;
		}
		return client;
	}
	
} )();

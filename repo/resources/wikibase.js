/**
 * JavaScript for 'wikibase' extension
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner at wikimedia.de >
 */

/**
 * Global 'Wikibase' extension singleton.
 * @since 0.1
 */
var wikibase = new ( function( mw, $, undefined ) {
	'use strict';

	/**
	 * event triggered after a new wikibase item was created and successfully stored in the database via an API call
	 * @see wikibase.ui.PropertyEditTool.EditableValue
	 *
	 * @param jQuery.Event event
	 * @param JSON apiResponse
	 */
	var onNewItemCreated = function( event, apiResponse ) {
		// remember a new items id globally when created!
		mw.config.set( 'wbItemId', apiResponse.id );

		// redirect to the newly created items site:
		var newItemTitle = new mw.Title( 'Data:' + 'q' + apiResponse.id );
		var newLocation = newItemTitle.getUrl() + '?wbitemcreated=yes';
		var usedLanguage = mw.util.getParamValue( 'uselang' );
		if( usedLanguage ) {
			// if language was set, make sure to propagate it through the redirect
			newLocation += '&uselang=' + mw.util.rawurlencode( usedLanguage );
		}

		window.location = newLocation;
		// TODO: this is not yet near perfect and a proper workflow hast to be created. The redirect as of now is
		//       rather ugly in its presentation to the user.
	};
	$( this ).on( 'newItemCreated', onNewItemCreated );


	/**
	 * Will hold a list of all the sites after getSites() was called. This will cache the result.
	 * @var wikibase.Site[]
	 */
	this._siteList = null;

	/**
	 * Returns an array with all the known sites.
	 *
	 * @return wikibase.Site[]
	 */
	this.getSites = function() {
		if( this._siteList !== null ) {
			// get cached list since this can be an expensive job to do
			return this._siteList;
		}

		// get all the details about all the sites:
		var sitesDetails = mw.config.get( 'wbSiteDetails' );
		this._siteList = {};

		for( var siteId in sitesDetails ) {
			if( sitesDetails.hasOwnProperty( siteId ) ) {
				var site = sitesDetails[ siteId ];
				site.id = siteId;
				this._siteList[ siteId ] =  new this.Site( site );
			}
		}

		return this._siteList;
	};

	/**
	 * Returns whether the Wikibase installation knows a site with a certain ID.
	 *
	 * @return bool
	 */
	this.hasSite = function ( siteId ) {
		return this.getSite( siteId ) !== null;
	};

	/**
	 * Returns a wikibase.Site object with details about a site by the sites site ID. If there is no
	 * site related to the given ID, null will be returned.
	 *
	 * @param int siteId
	 * @return wikibase.Site|null
	 */
	this.getSite = function( siteId ) {
		var sites = this.getSites();
		var site = sites[ siteId ];

		if( site === undefined ) {
			return null;
		}
		return site;
	};

} )( mediaWiki, jQuery );

window.wikibase = window.wb = wikibase; // global aliases
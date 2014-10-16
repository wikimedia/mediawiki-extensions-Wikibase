/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner at wikimedia.de >
 */
( function( wb, mw, $ ) {
	'use strict';

	/**
	 * @since 0.5
	 */
	wb.sites = new ( function WbSites() {

		/**
		 * Will hold a list of all the sites after getSites() was called. This will cache the result.
		 *
		 * TODO: This should go together with the old UI.
		 *
		 * @var Object
		 */
		this._siteList = null;

		/**
		 * Returns an array with all the known sites.
		 *
		 * @return {Object} Global site IDs as field names, related wb.Site objects as value.
		 */
		this.getSites = function() {
			if( this._siteList !== null ) {
				// get cached list since this can be an expensive job to do
				return this._siteList;
			}

			// get all the details about all the sites:
			var sitesDetails = mw.config.get( 'wbSiteDetails' ),
				siteDefinition,
				site;

			this._siteList = {};

			for( var globalSiteId in sitesDetails ) {
				siteDefinition = sitesDetails[ globalSiteId ];
				site = new wb.Site( siteDefinition );

				this._siteList[ globalSiteId ] = site;
			}

			return this._siteList;
		};

		/**
		 * Returns an array with all the known sites of one group.
		 *
		 * @return {Object} Global site IDs as field names, related wb.Site objects as value.
		 */
		this.getSitesOfGroup = function( groupId ) {
			var sitesOfGroup = {},
				sites = this.getSites();

			for( var siteId in sites ) {
				var site = sites[ siteId ];

				if( site.getGroup() === groupId ) {
					sitesOfGroup[ siteId ] = site;
				}
			}
			return sitesOfGroup;
		};

		/**
		 * Returns an array with all known site groups.
		 *
		 * @return string[]
		 */
		this.getSiteGroups = function() {
			var groups = [],
				sites = this.getSites();

			for( var siteId in sites ) {
				var site = sites[ siteId ],
					group = site.getGroup();

				if( $.inArray( group, groups ) === -1 ) {
					groups.push( group );
				}
			}
			return groups;
		};

		/**
		 * Returns whether the Wikibase installation knows a site with a certain ID.
		 *
		 * @return {boolean}
		 */
		this.hasSite = function ( siteId ) {
			return this.getSite( siteId ) !== null;
		};

		/**
		 * Returns a wikibase.Site object with details about a site by the sites ID. If there is no site
		 * related to the given ID, null will be returned.
		 *
		 * @param {string} siteId
		 * @return wikibase.Site|null
		 */
		this.getSite = function( siteId ) {
			var sites = this.getSites(),
				site = sites[ siteId ];

			if( site === undefined ) {
				return null;
			}
			return site;
		};

		/**
		 * Returns a wikibase.Site object with details about a site by the sites global ID. If there is
		 * no site related to the given ID, null will be returned.
		 *
		 * @deprecated Use getSite() instead. The meaning of the site ID has changed in 0.4, also see
		 *             the Site object.
		 *
		 * @param {string} globalSiteId
		 * @return wikibase.Site|null
		 */
		this.getSiteByGlobalId = function( globalSiteId ) {
			return this.getSite( globalSiteId );
		};

	} )( );

} )( wikibase, mediaWiki, jQuery );

/**
 * @license GPL-2.0-or-later
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */
( function ( wb ) {
	'use strict';

	var wbSites = new ( function WbSites() {

		/**
		 * Will hold a list of all the sites after getSites() was called. This will cache the result.
		 *
		 * TODO: This should go together with the old UI.
		 *
		 * @member Object
		 */
		this._siteList = null;

		/**
		 * Returns an array with all the known sites.
		 *
		 * @return {Object} Global site IDs as field names, related wb.Site objects as value.
		 */
		this.getSites = function () {
			if ( this._siteList !== null ) {
				// get cached list since this can be an expensive job to do
				return this._siteList;
			}

			// get all the details about all the sites:
			var sitesDetails = mw.config.get( 'wbSiteDetails' ),
				siteDefinition,
				site;

			this._siteList = {};

			for ( var globalSiteId in sitesDetails ) {
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
		this.getSitesOfGroup = function ( groupId ) {
			var sitesOfGroup = {},
				sites = this.getSites();

			for ( var siteId in sites ) {
				var site = sites[ siteId ];

				if ( site.getGroup() === groupId ) {
					sitesOfGroup[ siteId ] = site;
				}
			}
			return sitesOfGroup;
		};

		/**
		 * Returns a wikibase.Site object with details about a site by the sites ID. If there is no site
		 * related to the given ID, null will be returned.
		 *
		 * @param {string} siteId
		 * @return {wikibase.Site|null}
		 */
		this.getSite = function ( siteId ) {
			var sites = this.getSites(),
				site = sites[ siteId ];

			if ( site === undefined ) {
				return null;
			}
			return site;
		};

	} )();

	module.exports = wbSites;

}( wikibase ) );

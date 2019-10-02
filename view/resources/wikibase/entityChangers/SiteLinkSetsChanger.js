/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb ) {
	'use strict';

	var MODULE = wb.entityChangers,
		datamodel = require( 'wikibase.datamodel' );

	function chain( tasks ) {
		return tasks.reduce( function ( promise, task ) {
			return promise.then( task );
		}, $.Deferred().resolve().promise() );
	}

	/**
	 * @param {wikibase.api.RepoApi} api
	 * @param {wikibase.RevisionStore} revisionStore
	 * @param {datamodel.Entity} entity
	 */
	var SELF = MODULE.SiteLinkSetsChanger = function WbEntityChangersSiteLinkSetsChanger( api, revisionStore, entity ) {
		this._siteLinksChanger = new MODULE.SiteLinksChanger( api, revisionStore, entity );
		this._entity = entity;
	};

	$.extend( SELF.prototype, {
		/**
		 * @type {datamodel.Entity}
		 */
		_entity: null,

		/**
		 * @type {wikibase.entityChangers.SiteLinksChanger}
		 */
		_siteLinksChanger: null,

		/**
		 * @param {datamodel.SiteLinkSet} newSiteLinkSet
		 * @param {datamodel.SiteLinkSet} oldSiteLinkSet
		 * @return {jQuery.Promise}
		 *         Resolved parameters:
		 *         - {string} The saved SiteLinkSet
		 *         Rejected parameters:
		 *         - {wikibase.api.RepoApiError}
		 */
		save: function ( newSiteLinkSet, oldSiteLinkSet ) {
			function getRemovedSiteLinkIds() {
				var currentSiteIds = newSiteLinkSet.getKeys();
				var removedSiteLinkIds = [];

				oldSiteLinkSet.each( function ( siteId ) {
					if ( currentSiteIds.indexOf( siteId ) === -1 ) {
						removedSiteLinkIds.push( siteId );
					}
				} );

				return removedSiteLinkIds;
			}

			function getDiffValue() {
				var siteLinks = [],
					unchangedSiteLinks = [];
				siteLinks = siteLinks.concat( getRemovedSiteLinkIds().map( function ( siteId ) {
					return new datamodel.SiteLink( siteId, '' );
				} ) );

				newSiteLinkSet.each( function ( site, sitelink ) {
					if ( !sitelink.equals( oldSiteLinkSet.getItemByKey( site ) ) ) {
						siteLinks.push( sitelink );
					} else {
						unchangedSiteLinks.push( sitelink );
					}
				} );
				return { changed: siteLinks, unchanged: unchangedSiteLinks };
			}

			var diffValue = getDiffValue();
			var siteLinksChanger = this._siteLinksChanger;
			var resultValue = diffValue.unchanged;

			return chain( diffValue.changed.map( function ( siteLink ) {
				return function () {
					return siteLinksChanger.setSiteLink( siteLink ).done( function ( savedSiteLink ) {
						if ( savedSiteLink ) { // Is null if a site link was removed
							resultValue.push( savedSiteLink );
						}
					} );
				};
			} ) ).then( function () {
				return new datamodel.SiteLinkSet( resultValue.sort( function ( s1, s2 ) {
					return s1.getSiteId().localeCompare( s2.getSiteId() );
				} ) );
			} );
		}

	} );

}( wikibase ) );

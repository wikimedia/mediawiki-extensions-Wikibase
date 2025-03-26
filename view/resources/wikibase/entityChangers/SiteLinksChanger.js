/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb ) {
	'use strict';

	var MODULE = wb.entityChangers,
		datamodel = require( 'wikibase.datamodel' );
	MODULE.SiteLinksChanger = class {
		/**
		 * @param {wikibase.api.RepoApi} api
		 * @param {wikibase.RevisionStore} revisionStore
		 * @param {datamodel.Entity} entity
		 */
		constructor( api, revisionStore, entity ) {
			/**
			 * @type {wikibase.api.RepoApi}
			 */
			this._api = api;
			/**
			 * @type {wikibase.RevisionStore}
			 */
			this._revisionStore = revisionStore;
			/**
			 * @type {datamodel.Entity}
			 */
			this._entity = entity;
		}

		/**
		 * @param {datamodel.SiteLink} siteLink
		 * @param {datamodel.TempUserWatcher} tempUserWatcher
		 * @return {jQuery.Promise}
		 *         Resolved parameters:
		 *         - {string} The saved siteLink
		 *         Rejected parameters:
		 *         - {wikibase.api.RepoApiError}
		 */
		setSiteLink( siteLink, tempUserWatcher ) {
			var self = this,
				deferred = $.Deferred();

			this._api.setSitelink(
				this._entity.getId(),
				this._revisionStore.getSitelinksRevision( siteLink.getSiteId() ),
				siteLink.getSiteId(),
				siteLink.getPageName(),
				siteLink.getBadges()
			)
			.done( ( result ) => {
				var siteId = siteLink.getSiteId(),
					resultData = result.entity.sitelinks[ siteId ];

				// Update revision store
				self._revisionStore.setSitelinksRevision( result.entity.lastrevid, siteId );

				// Handle TempUser if one is created
				tempUserWatcher.processApiResult( result );

				// FIXME: Maybe check API's return value?

				// FIXME: Introduce Item.setSiteLinks

				deferred.resolve(
					typeof resultData.removed !== 'undefined'
						? null
						: new datamodel.SiteLink(
							siteId,
							resultData.title,
							resultData.badges
						)
				);
			} )
			.fail( ( errorCode, error ) => {
				deferred.reject( wb.api.RepoApiError.newFromApiResponse(
					error,
					siteLink.getPageName() === '' ? 'remove' : 'save' )
				);
			} );

			return deferred.promise();
		}
	};
}( wikibase ) );

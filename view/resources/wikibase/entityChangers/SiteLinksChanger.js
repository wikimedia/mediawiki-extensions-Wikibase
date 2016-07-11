/**
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function( wb, $ ) {
	'use strict';

	var MODULE = wb.entityChangers;
	/**
	 * @param {wikibase.api.RepoApi} api
	 * @param {wikibase.RevisionStore} revisionStore
	 * @param {wikibase.datamodel.Entity} entity
	 */
	var SELF = MODULE.SiteLinksChanger = function WbEntityChangersSiteLinksChanger( api, revisionStore, entity ) {
		this._api = api;
		this._revisionStore = revisionStore;
		this._entity = entity;
	};

	$.extend( SELF.prototype, {
		/**
		 * @type {wikibase.datamodel.Entity}
		 */
		_entity: null,

		/**
		 * @type {wikibase.RevisionStore}
		 */
		_revisionStore: null,

		/**
		 * @type {wikibase.api.RepoApi}
		 */
		_api: null,

		/**
		 * @param {wikibase.datamodel.SiteLink} siteLink
		 * @return {jQuery.Promise}
		 *         Resolved parameters:
		 *         - {string} The saved siteLink
		 *         Rejected parameters:
		 *         - {wikibase.api.RepoApiError}
		 */
		setSiteLink: function( siteLink ) {
			var self = this,
				deferred = $.Deferred();

			this._api.setSitelink(
				this._entity.getId(),
				this._revisionStore.getSitelinksRevision( siteLink.getSiteId() ),
				siteLink.getSiteId(),
				siteLink.getPageName(),
				siteLink.getBadges()
			)
			.done( function( result ) {
				var siteId = siteLink.getSiteId(),
					resultData = result.entity.sitelinks[siteId];

				// Update revision store
				self._revisionStore.setSitelinksRevision( result.entity.lastrevid, siteId );

				// FIXME: Maybe check API's return value?

				// FIXME: Introduce Item.setSiteLinks

				deferred.resolve(
					resultData.hasOwnProperty( 'removed' )
						? null
						: new wb.datamodel.SiteLink(
							siteId,
							resultData.title,
							resultData.badges
						)
				);
			} )
			.fail( function( errorCode, error ) {
				deferred.reject( wb.api.RepoApiError.newFromApiResponse(
					error,
					siteLink.getPageName() === '' ? 'remove' : 'save' )
				);
			} );

			return deferred.promise();
		}
	} );
}( wikibase, jQuery ) );

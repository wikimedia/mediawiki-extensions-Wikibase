/**
 * @licence GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */
( function( wb, $ ) {
	'use strict';

	var MODULE = wb.entityChangers;
	/**
	 * @param {wikibase.RepoApi}
	 * @param {wikibase.RevisionStore}
	 * @param {wikibase.datamodel.Entity}
	 */
	var SELF = MODULE.SiteLinksChanger = function( api, revisionStore, entity ) {
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
		 * @type {wikibase.RepoApi}
		 */
		_api: null,

		/**
		 * @param {wikibase.datamodel.SiteLink} siteLink
		 * @param {string} language
		 * @return {jQuery.Promise}
		 *         Resolved parameters:
		 *         - {string} The saved siteLink
		 *         Rejected parameters:
		 *         - {wikibase.RepoApiError}
		 */
		setSiteLink: function( siteLink, language ) {
			var self = this;
			var deferred = $.Deferred();

			this._api.setSitelink(
				this._entity.getId(),
				this._revisionStore.getSitelinksRevision( siteLink.getSiteId() ),
				siteLink.getSiteId(),
				siteLink.getPageName(),
				siteLink.getBadges()
			)
			.done( function( result ) {
				var savedSiteLink = new wb.datamodel.SiteLink(
					siteLink.getSiteId(),
					result.entity.sitelinks[siteLink.getSiteId()].title
				);

				// Update revision store:
				self._revisionStore.setSitelinksRevision(
					result.entity.lastrevid,
					siteLink.getSiteId()
				);

				// FIXME: Maybe check API's return value?

				// FIXME: Introduce Item.setSiteLinks

				deferred.resolve( savedSiteLink );
			} )
			.fail( function( errorCode, error ) {
				deferred.reject( wb.RepoApiError.newFromApiResponse(
					error,
					siteLink.getPageName() === '' ? 'remove' : 'save' )
				);
			} );

			return deferred.promise();
		}
	} );
} ( wikibase, jQuery ) );

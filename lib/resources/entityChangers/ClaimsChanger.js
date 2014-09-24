/**
 * @licence GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */
( function( wb, $ ) {
	'use strict';

	var MODULE = wb.entityChangers;
	/**
	 * @param {wb.RepoApi}
	 * @param {wb.RevisionStore}
	 * @param {wb.datamodel.Entity}
	 */
	var SELF = MODULE.ClaimsChanger = function( api, revisionStore, entity ) {
		this._api = api;
		this._revisionStore = revisionStore;
		this._entity = entity;
	};

	$.extend( SELF.prototype, {
		/**
		 * @type {wb.datamodel.Entity}
		 */
		_entity: null,

		/**
		 * @type {wb.RevisionStore}
		 */
		_revisionStore: null,

		/**
		 * @type {wb.RepoApi}
		 */
		_api: null,

		removeClaim: function( claim ) {
			var self = this;
			var guid = claim.getGuid();

			return this._api.removeClaim( guid, this._revisionStore.getClaimRevision( guid ) )
			.done( function( response ) {
				self._revisionStore.setClaimRevision( response.pageinfo.lastrevid, guid );

				// FIXME: Maybe check API's return value?

				// FIXME: Introduce Item.setClaims
			} );
		},

		setClaim: function( claim, index ) {
			var self = this;
			var deferred = $.Deferred();

			this._api.setClaim(
				claim.toJSON(),
				this._revisionStore.getClaimRevision( claim.getGuid() ),
				index
			)
			.done( function( result ) {
				var savedClaim = wb.datamodel.Claim.newFromJSON( result.claim );
				var pageInfo = result.pageinfo;

				// Update revision store:
				self._revisionStore.setClaimRevision( pageInfo.lastrevid, savedClaim.getGuid() );

				// FIXME: Maybe check API's return value?

				// FIXME: Introduce Item.setClaims

				deferred.resolve( savedClaim );
			} )
			.fail( function( error ) {
				deferred.reject( error );
			} );

			return deferred.promise();
		}
	} );
} ( wikibase, jQuery ) );

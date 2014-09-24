/**
 * @licence GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */
( function( wb, $ ) {
	'use strict';

var MODULE = wb.entityChangers;

/**
 * @constructor
 * @since 0.5
 *
 * @param {wikibase.RepoApi} api
 * @param {wikibase.RevisionStore} revisionStore
 * @param {wikibase.datamodel.Entity} entity
 */
var SELF = MODULE.ClaimsChanger = function( api, revisionStore, entity ) {
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
	 * @param {wikibase.datamodel.Claim} claim
	 * @return {jQuery.Promise}
	 *         No resolved parameters.
	 *         Rejected parameters:
	 *         - {wikibase.RepoApiError}
	 */
	removeClaim: function( claim ) {
		var deferred = $.Deferred();
		var self = this;
		var guid = claim.getGuid();

		this._api.removeClaim( guid, this._revisionStore.getClaimRevision( guid ) )
		.done( function( response ) {
			self._revisionStore.setClaimRevision( response.pageinfo.lastrevid, guid );

			// FIXME: Introduce Item.setClaims
			deferred.resolve();
		} )
		.fail( function( errorCode, error ) {
			deferred.reject( wb.RepoApiError.newFromApiResponse( error, 'remove' ) );
		} );

		return deferred.promise();
	},

	/**
	 * @param {wikibase.datamodel.Claim} claim
	 * @param {number} index
	 * @return {jQuery.Promise}
	 *         Resolved parameters:
	 *         - {wikibase.datamodel.Claim} The saved claim
	 *         Rejected parameters:
	 *         - {wikibase.RepoApiError}
	 */
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

			// FIXME: Introduce Item.setClaims

			deferred.resolve( savedClaim );
		} )
		.fail( function( errorCode, error ) {
			deferred.reject( wb.RepoApiError.newFromApiResponse( error, 'save' ) );
		} );

		return deferred.promise();
	}
} );

} ( wikibase, jQuery ) );

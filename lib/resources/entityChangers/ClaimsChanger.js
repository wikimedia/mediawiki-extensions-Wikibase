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
 * @param {wikibase.serialization.ClaimSerializer} claimSerializer
 * @param {wikibase.serialization.ClaimDeserializer} claimDeserializer
 * @param {wikibase.serialization.StatementSerializer} statementSerializer
 * @param {wikibase.serialization.StatementDeserializer} statementDeserializer
 */
var SELF = MODULE.ClaimsChanger = function( api, revisionStore, entity, claimSerializer, claimDeserializer, statementSerializer, statementDeserializer ) {
	this._api = api;
	this._revisionStore = revisionStore;
	this._entity = entity;
	this._claimSerializer = claimSerializer;
	this._claimDeserializer = claimDeserializer;
	this._statementSerializer = statementSerializer;
	this._statementDeserializer = statementDeserializer;
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
	 * @type {wikibase.serialization.ClaimSerializer}
	 */
	_claimSerializer: null,

	/**
	 * @type {wikibase.serialization.ClaimDeserializer}
	 */
	_claimDeserializer: null,

	/**
	 * @type {wikibase.serialization.StatementSerializer}
	 */
	_statementSerializer: null,

	/**
	 * @type {wikibase.serialization.StatementDeserializer}
	 */
	_statementDeserializer: null,

	/**
	 * @param {wikibase.datamodel.Statement} statement
	 * @return {jQuery.Promise}
	 *         No resolved parameters.
	 *         Rejected parameters:
	 *         - {wikibase.RepoApiError}
	 */
	removeStatement: function( statement ) {
		var deferred = $.Deferred();
		var self = this;
		var guid = statement.getClaim().getGuid();

		this._api.removeClaim( guid, this._revisionStore.getClaimRevision( guid ) )
		.done( function( response ) {
			self._revisionStore.setClaimRevision( response.pageinfo.lastrevid, guid );

			// FIXME: Set statement on this._entity
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
			this._claimSerializer.serialize( claim ),
			this._revisionStore.getClaimRevision( claim.getGuid() ),
			index
		)
		.done( function( result ) {
			var savedClaim = self._claimDeserializer.deserialize( result.claim );
			var pageInfo = result.pageinfo;

			// Update revision store:
			self._revisionStore.setClaimRevision( pageInfo.lastrevid, savedClaim.getGuid() );

			// FIXME: Set claim on this._entity

			deferred.resolve( savedClaim );
		} )
		.fail( function( errorCode, error ) {
			deferred.reject( wb.RepoApiError.newFromApiResponse( error, 'save' ) );
		} );

		return deferred.promise();
	},

	/**
	 * @param {wikibase.datamodel.Statement} statement
	 * @param {number} index
	 * @return {jQuery.Promise}
	 *         Resolved parameters:
	 *         - {wikibase.datamodel.Statement} The saved statement
	 *         Rejected parameters:
	 *         - {wikibase.RepoApiError}
	 */
	setStatement: function( statement, index ) {
		var self = this;
		var deferred = $.Deferred();

		this._api.setClaim(
			this._statementSerializer.serialize( statement ),
			this._revisionStore.getClaimRevision( statement.getClaim().getGuid() ),
			index
		)
		.done( function( result ) {
			var savedStatement = self._statementDeserializer.deserialize( result.claim );
			var pageInfo = result.pageinfo;

			// Update revision store:
			self._revisionStore.setClaimRevision( pageInfo.lastrevid, savedStatement.getClaim().getGuid() );

			// FIXME: Set statement on this._entity

			deferred.resolve( savedStatement );
		} )
		.fail( function( errorCode, error ) {
			deferred.reject( wb.RepoApiError.newFromApiResponse( error, 'save' ) );
		} );

		return deferred.promise();
	}
} );

} ( wikibase, jQuery ) );

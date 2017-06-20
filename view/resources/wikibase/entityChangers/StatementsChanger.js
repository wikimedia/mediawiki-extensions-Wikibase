/**
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb, $ ) {
	'use strict';

	var MODULE = wb.entityChangers;

	/**
	 * @constructor
	 *
	 * @param {wikibase.api.RepoApi} api
	 * @param {wikibase.RevisionStore} revisionStore
	 * @param {wikibase.datamodel.Entity} entity
	 * @param {wikibase.serialization.StatementSerializer} statementSerializer
	 * @param {wikibase.serialization.StatementDeserializer} statementDeserializer
	 * @param {Function} [fireHook] called after a statement has been saved (wikibase.statement.saved) or deleted (wikibase.statement.deleted), with the hook name (wikibase.…), entity ID and statement ID as arguments.
	 */
	var SELF = MODULE.StatementsChanger = function WbEntityChangersStatementsChanger(
		api,
		revisionStore,
		entity,
		statementSerializer,
		statementDeserializer,
		fireHook
	) {
		this._api = api;
		this._revisionStore = revisionStore;
		this._entity = entity;
		this._statementSerializer = statementSerializer;
		this._statementDeserializer = statementDeserializer;
		this._fireHook = fireHook || function () {
		};
	};

	$.extend( SELF.prototype, {
		/**
		 * @type {wikibase.api.RepoApi}
		 */
		_api: null,

		/**
		 * @type {wikibase.RevisionStore}
		 */
		_revisionStore: null,

		/**
		 * @type {wikibase.datamodel.Entity}
		 */
		_entity: null,

		/**
		 * @type {wikibase.serialization.StatementSerializer}
		 */
		_statementSerializer: null,

		/**
		 * @type {wikibase.serialization.StatementDeserializer}
		 */
		_statementDeserializer: null,

		/**
		 * @type {Function}
		 */
		_fireHook: null,

		/**
		 * @param {wikibase.datamodel.Statement} statement
		 * @return {jQuery.Promise}
		 *         No resolved parameters.
		 *         Rejected parameters:
		 *         - {wikibase.api.RepoApiError}
		 */
		remove: function ( statement ) {
			var deferred = $.Deferred(),
				self = this,
				guid = statement.getClaim().getGuid();

			this._api.removeClaim( guid, this._revisionStore.getClaimRevision( guid ) )
			.done( function ( response ) {
				self._revisionStore.setClaimRevision( response.pageinfo.lastrevid, guid );

				// FIXME: Set statement on this._entity

				deferred.resolve();

				self._fireHook( 'wikibase.statement.removed', self._entity.getId(), guid );
			} )
			.fail( function ( errorCode, error ) {
				deferred.reject( wb.api.RepoApiError.newFromApiResponse( error, 'remove' ) );
			} );

			return deferred.promise();
		},

		/**
		 * @param {wikibase.datamodel.Statement} statement
		 * @return {Object} jQuery.Promise
		 *         Resolved parameters:
		 *         - {wikibase.datamodel.Statement} The saved statement
		 *         Rejected parameters:
		 *         - {wikibase.api.RepoApiError}
		 */
		save: function ( statement ) {
			var self = this,
				deferred = $.Deferred();

			this._api.setClaim(
				this._statementSerializer.serialize( statement ),
				this._revisionStore.getClaimRevision( statement.getClaim().getGuid() )
			)
			.done( function ( result ) {
				var savedStatement = self._statementDeserializer.deserialize( result.claim ),
					guid = savedStatement.getClaim().getGuid(),
					pageInfo = result.pageinfo;

				// Update revision store:
				self._revisionStore.setClaimRevision( pageInfo.lastrevid, guid );

				// FIXME: Set statement on this._entity

				deferred.resolve( savedStatement );

				self._fireHook( 'wikibase.statement.saved', self._entity.getId(), guid );
			} )
			.fail( function ( errorCode, error ) {
				deferred.reject( wb.api.RepoApiError.newFromApiResponse( error, 'save' ) );
			} );

			return deferred.promise();
		}
	} );

}( wikibase, jQuery ) );

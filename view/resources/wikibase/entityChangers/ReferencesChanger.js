/**
 * @license GPL-2.0+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */
( function( wb, $ ) {
	'use strict';

var MODULE = wb.entityChangers;

/**
 * @constructor
 * @since 0.5
 *
 * @param {wikibase.api.RepoApi} api
 * @param {wikibase.RevisionStore} revisionStore
 * @param {wikibase.datamodel.Entity} entity
 * @param {wikibase.serialization.ReferenceSerializer} referenceSerializer
 * @param {wikibase.serialization.ReferenceDeserializer} referenceDeserializer
 */
var SELF = MODULE.ReferencesChanger = function WbEntityChangersReferencesChanger(
	api,
	revisionStore,
	entity,
	referenceSerializer,
	referenceDeserializer
) {
	this._api = api;
	this._revisionStore = revisionStore;
	this._entity = entity;
	this._referenceSerializer = referenceSerializer;
	this._referenceDeserializer = referenceDeserializer;
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
	 * @type {wikibase.serialization.ReferenceSerializer}
	 */
	_referenceSerializer: null,

	/**
	 * @type {wikibase.serialization.ReferenceDeserializer}
	 */
	_referenceDeserializer: null,

	/**
	 * @param {string} statementGuid
	 * @param {wikibase.datamodel.Reference} reference
	 * @return {jQuery.Promise}
	 *         No resolved parameters.
	 *         Rejected parameters:
	 *         - {wikibase.api.RepoApiError}
	 */
	removeReference: function( statementGuid, reference ) {
		var deferred = $.Deferred(),
			self = this;

		this._api.removeReferences(
			statementGuid,
			reference.getHash(),
			this._revisionStore.getClaimRevision( statementGuid )
		)
		.done( function( result ) {
			self._revisionStore.setClaimRevision( result.pageinfo.lastrevid, statementGuid );

			// FIXME: Update self._entity
			deferred.resolve();
		} )
		.fail( function( errorCode, error ) {
			deferred.reject( wb.api.RepoApiError.newFromApiResponse( error, 'remove' ) );
		} );

		return deferred.promise();
	},

	/**
	 * @param {string} statementGuid
	 * @param {wikibase.datamodel.Reference} reference
	 * @param {number} [index]
	 * @return {jQuery.Promise}
	 *         Resolved parameters:
	 *         - {wikibase.datamodel.Reference} The saved reference
	 *         Rejected parameters:
	 *         - {wikibase.api.RepoApiError}
	 */
	setReference: function( statementGuid, reference, index ) {
		var deferred = $.Deferred(),
			self = this;

		this._api.setReference(
			statementGuid,
			this._referenceSerializer.serialize( reference ).snaks,
			this._revisionStore.getClaimRevision( statementGuid ),
			reference.getHash(),
			index
		)
		.done( function( result ) {
			var savedReference = self._referenceDeserializer.deserialize( result.reference ),
				pageInfo = result.pageinfo;

			// Update revision store:
			self._revisionStore.setClaimRevision( pageInfo.lastrevid, statementGuid );

			// FIXME: Update self._entity

			deferred.resolve( savedReference );
		} )
		.fail( function( errorCode, error ) {
			deferred.reject( wb.api.RepoApiError.newFromApiResponse( error, 'save' ) );
		} );

		return deferred.promise();
	}
} );

}( wikibase, jQuery ) );

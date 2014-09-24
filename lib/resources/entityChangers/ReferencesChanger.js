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
var SELF = MODULE.ReferencesChanger = function( api, revisionStore, entity ) {
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
	 * @param {string} statementGuid
	 * @param {wikibase.datamodel.Reference} reference
	 * @param {number} index
	 * @return {jQuery.Promise}
	 *         No resolved parameters.
	 *         Rejected parameters:
	 *         - {wikibase.RepoApiError}
	 */
	removeReference: function( statementGuid, reference, index ) {
		var deferred = $.Deferred();
		var self = this;

		this._api.removeReferences(
			statementGuid,
			reference.getHash(),
			this._revisionStore.getClaimRevision( statementGuid ),
			index
		)
		.done( function( result ) {
			self._revisionStore.setClaimRevision( result.pageinfo, statementGuid );

			// FIXME: Introduce Item.setReferences
			deferred.resolve();
		} )
		.fail( function( errorCode, error ) {
			deferred.reject( wb.RepoApiError.newFromApiResponse( error, 'remove' ) );
		} );

		return deferred.promise();
	},

	/**
	 * @param {string} statementGuid
	 * @param {wikibase.datamodel.Reference} reference
	 * @param {number} index
	 * @return {jQuery.Promise}
	 *         Resolved parameters:
	 *         - {wikibase.datamodel.Reference} The saved reference
	 *         Rejected parameters:
	 *         - {wikibase.RepoApiError}
	 */
	setReference: function( statementGuid, reference, index ) {
		var deferred = $.Deferred();
		var self = this;
		this._api.setReference(
			statementGuid,
			reference.getSnaks().toJSON(),
			this._revisionStore.getClaimRevision( statementGuid ),
			reference.getHash(),
			index
		)
		.done( function( result ) {
			var savedReference = wb.datamodel.Reference.newFromJSON( result.reference );
			var pageInfo = result.pageinfo;

			// Update revision store:
			self._revisionStore.setClaimRevision( pageInfo.lastrevid, statementGuid );

			// FIXME: Introduce Item.setReferences

			deferred.resolve( savedReference );
		} )
		.fail( function( errorCode, error ) {
			deferred.reject( wb.RepoApiError.newFromApiResponse( error, 'save' ) );
		} );

		return deferred.promise();
	}
} );

} ( wikibase, jQuery ) );

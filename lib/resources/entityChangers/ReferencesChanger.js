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
	var SELF = MODULE.ReferencesChanger = function( api, revisionStore, entity ) {
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

		removeReference: function( statementGuid, reference, index ) {
			var self = this;

			return this._api.removeReferences(
				statementGuid,
				reference.getHash(),
				this._revisionStore.getClaimRevision( statementGuid ),
				index
			)
			.done( function( result ) {
				self._revisionStore.setClaimRevision( result.pageinfo, statementGuid );

				// FIXME: Maybe check API's return value?

				// FIXME: Introduce Item.setReferences
			} );
		},

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

				// FIXME: Maybe check API's return value?

				// FIXME: Introduce Item.setReferences

				deferred.resolve( savedReference );
			} )
	    .fail( function( errorCode, errorResult ) {
	      deferred.reject( errorResult );
	    } );

			return deferred.promise();
		}
	} );
} ( wikibase, jQuery ) );

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
	var SELF = MODULE.DescriptionsChanger = function WbEntityChangersDescriptionsChanger( api, revisionStore, entity ) {
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
		 * @param {wikibase.datamodel.Term} description
		 * @return {jQuery.Promise}
		 *         Resolved parameters:
		 *         - {string} The saved description
		 *         Rejected parameters:
		 *         - {wikibase.api.RepoApiError}
		 */
		setDescription: function( description ) {
			var self = this,
				deferred = $.Deferred(),
				language = description.getLanguageCode();

			this._api.setDescription(
				this._entity.getId(),
				this._revisionStore.getDescriptionRevision(),
				description.getText(),
				language
			)
			.done( function( result ) {
				var savedText = result.entity.descriptions[language].value,
					savedTerm = savedText ? new wb.datamodel.Term( language, savedText ) : null;

				// Update revision store:
				self._revisionStore.setDescriptionRevision( result.entity.lastrevid );

				// FIXME: Maybe check API's return value?

				// FIXME: Introduce Item.setDescriptions

				deferred.resolve( savedTerm );
			} )
			.fail( function( errorCode, error ) {
				deferred.reject( wb.api.RepoApiError.newFromApiResponse( error, 'save' ) );
			} );

			return deferred.promise();
		}
	} );
}( wikibase, jQuery ) );

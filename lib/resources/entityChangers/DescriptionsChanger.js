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
	var SELF = MODULE.DescriptionsChanger = function( api, revisionStore, entity ) {
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
		 * @param {string} description
		 * @param {string} language
		 * @return {jQuery.Promise}
		 *         Resolved parameters:
		 *         - {string} The saved description
		 *         Rejected parameters:
		 *         - {wikibase.RepoApiError}
		 */
		setDescription: function( description, language ) {
			var self = this;
			var deferred = $.Deferred();

			this._api.setDescription(
				this._entity.getId(),
				this._revisionStore.getDescriptionRevision(),
				description,
				language
			)
			.done( function( result ) {
				var savedDescription = result.entity.descriptions[language].value;

				// Update revision store:
				self._revisionStore.setDescriptionRevision( result.entity.lastrevid );

				// FIXME: Maybe check API's return value?

				// FIXME: Introduce Item.setDescriptions

				deferred.resolve( savedDescription );
			} )
			.fail( function( errorCode, error ) {
				deferred.reject( wb.RepoApiError.newFromApiResponse( error, 'save' ) );
			} );

			return deferred.promise();
		}
	} );
} ( wikibase, jQuery ) );

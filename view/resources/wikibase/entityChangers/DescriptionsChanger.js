/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb ) {
	'use strict';

	var MODULE = wb.entityChangers,
		datamodel = require( 'wikibase.datamodel' );

	/**
	 * @param {wikibase.api.RepoApi} api
	 * @param {wikibase.RevisionStore} revisionStore
	 * @param {datamodel.Entity} entity
	 */
	MODULE.DescriptionsChanger = class {
		constructor( api, revisionStore, entity ) {
			/**
			 * @type {wikibase.api.RepoApi}
			 */
			this._api = api;
			/**
			 * @type {wikibase.RevisionStore}
			 */
			this._revisionStore = revisionStore;
			/**
			 * @type {datamodel.Entity}
			 */
			this._entity = entity;
		}

		/**
		 * @param {datamodel.Term} description
		 * @param {entityChangers.TempUserWatcher} tempUserWatcher
		 * @return {jQuery.Promise}
		 *         Resolved parameters:
		 *         - {string} The saved description
		 *         Rejected parameters:
		 *         - {wikibase.api.RepoApiError}
		 */
		setDescription( description, tempUserWatcher ) {
			var self = this,
				deferred = $.Deferred(),
				language = description.getLanguageCode();

			this._api.setDescription(
				this._entity.getId(),
				this._revisionStore.getDescriptionRevision(),
				description.getText(),
				language
			)
			.done( ( result ) => {
				var savedText = result.entity.descriptions[ language ].value,
					savedTerm = savedText ? new datamodel.Term( language, savedText ) : null;

				// Update revision store:
				self._revisionStore.setDescriptionRevision( result.entity.lastrevid );
				// Handle TempUser if one is created
				tempUserWatcher.processApiResult( result );

				// FIXME: Maybe check API's return value?

				// FIXME: Introduce Item.setDescriptions

				deferred.resolve( savedTerm );
			} )
			.fail( ( errorCode, error ) => {
				deferred.reject( wb.api.RepoApiError.newFromApiResponse( error, 'save' ) );
			} );

			return deferred.promise();
		}
	};
}( wikibase ) );

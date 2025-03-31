/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb ) {
	'use strict';

	var MODULE = wb.entityChangers,
		datamodel = require( 'wikibase.datamodel' );

	MODULE.LabelsChanger = class {
		/**
		 * @param {wikibase.api.RepoApi} api
		 * @param {wikibase.RevisionStore} revisionStore
		 * @param {datamodel.Entity} entity
		 */
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
		 * @param {datamodel.Term} label
		 * @param {entityChangers.TempUserWatcher} tempUserWatcher
		 * @return {jQuery.Promise}
		 *         Resolved parameters:
		 *         - {string} The saved label
		 *         Rejected parameters:
		 *         - {wikibase.api.RepoApiError}
		 */
		setLabel( label, tempUserWatcher ) {
			var self = this,
				deferred = $.Deferred(),
				language = label.getLanguageCode();

			this._api.setLabel(
				this._entity.getId(),
				this._revisionStore.getLabelRevision(),
				label.getText(),
				language
			)
			.done( ( result ) => {
				var savedText = result.entity.labels[ language ].value,
					savedTerm = savedText ? new datamodel.Term( language, savedText ) : null;

				// Update revision store:
				self._revisionStore.setLabelRevision( result.entity.lastrevid );
				// Handle TempUser if one is created
				tempUserWatcher.processApiResult( result );

				// FIXME: Maybe check API's return value?

				// FIXME: Introduce Item.setLabels

				deferred.resolve( savedTerm );
			} )
			.fail( ( errorCode, error ) => {
				deferred.reject( wb.api.RepoApiError.newFromApiResponse( error, 'save' ) );
			} );

			return deferred.promise();
		}
	};
}( wikibase ) );

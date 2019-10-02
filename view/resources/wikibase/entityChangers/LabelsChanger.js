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
	var SELF = MODULE.LabelsChanger = function WbEntityChangersLabelsChanger( api, revisionStore, entity ) {
		this._api = api;
		this._revisionStore = revisionStore;
		this._entity = entity;
	};

	$.extend( SELF.prototype, {
		/**
		 * @type {datamodel.Entity}
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
		 * @param {datamodel.Term} label
		 * @return {jQuery.Promise}
		 *         Resolved parameters:
		 *         - {string} The saved label
		 *         Rejected parameters:
		 *         - {wikibase.api.RepoApiError}
		 */
		setLabel: function ( label ) {
			var self = this,
				deferred = $.Deferred(),
				language = label.getLanguageCode();

			this._api.setLabel(
				this._entity.getId(),
				this._revisionStore.getLabelRevision(),
				label.getText(),
				language
			)
			.done( function ( result ) {
				var savedText = result.entity.labels[ language ].value,
					savedTerm = savedText ? new datamodel.Term( language, savedText ) : null;

				// Update revision store:
				self._revisionStore.setLabelRevision( result.entity.lastrevid );

				// FIXME: Maybe check API's return value?

				// FIXME: Introduce Item.setLabels

				deferred.resolve( savedTerm );
			} )
			.fail( function ( errorCode, error ) {
				deferred.reject( wb.api.RepoApiError.newFromApiResponse( error, 'save' ) );
			} );

			return deferred.promise();
		}
	} );
}( wikibase ) );

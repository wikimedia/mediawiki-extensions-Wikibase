/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb ) {
	'use strict';

	var MODULE = wb.entityChangers,
		datamodel = require( 'wikibase.datamodel' );

	MODULE.AliasesChanger = class {
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
		 * @param {datamodel.MultiTerm} aliases
		 * @param {entityChangers.TempUserWatcher} tempUserWatcher
		 * @return {jQuery.Promise}
		 *         No resolved parameters.
		 *         Rejected parameters:
		 *         - {wikibase.api.RepoApiError}
		 */
		setAliases( aliases, tempUserWatcher ) {
			var deferred = $.Deferred(),
				self = this,
				language = aliases.getLanguageCode(),
				initialAliases = this._getInitialAliases( language );

			this._api.setAliases(
				this._entity.getId(),
				this._revisionStore.getAliasesRevision(),
				this._getNewAliasesTexts( aliases, initialAliases ),
				this._getRemovedAliasesTexts( aliases, initialAliases ),
				language
			)
			.done( ( response ) => {
				self._revisionStore.setAliasesRevision( response.entity.lastrevid );

				self._entity.getFingerprint().setAliases( language, aliases );

				// Handle TempUser if one is created
				tempUserWatcher.processApiResult( response );

				var texts = [];
				if ( response.entity.aliases && response.entity.aliases[ language ] ) {
					texts = response.entity.aliases[ language ].map( ( value ) => value.value );
				}
				deferred.resolve( new datamodel.MultiTerm( language, texts ) );
			} )
			.fail( ( errorCode, errorObject ) => {
				deferred.reject( wb.api.RepoApiError.newFromApiResponse( errorObject, 'save' ) );
			} );

			return deferred.promise();
		}

		/**
		 * @param {string} language
		 * @return {datamodel.MultiTerm}
		 */
		_getInitialAliases( language ) {
			return this._entity.getFingerprint().getAliasesFor( language )
				|| new datamodel.MultiTerm( language, [] );
		}

		/**
		 * @param {datamodel.MultiTerm} currentAliases
		 * @param {datamodel.MultiTerm} initialAliases
		 * @return {string[]}
		 */
		_getNewAliasesTexts( currentAliases, initialAliases ) {
			var currentTexts = currentAliases.getTexts(),
				initialTexts = initialAliases.getTexts(),
				newAliases = [];

			for ( var i = 0; i < currentTexts.length; i++ ) {
				if ( initialTexts.indexOf( currentTexts[ i ] ) === -1 ) {
					newAliases.push( currentTexts[ i ] );
				}
			}

			return newAliases;
		}

		/**
		 * @param {datamodel.MultiTerm} currentAliases
		 * @param {datamodel.MultiTerm} initialAliases
		 * @return {string[]}
		 */
		_getRemovedAliasesTexts( currentAliases, initialAliases ) {
			var currentTexts = currentAliases.getTexts(),
				initialTexts = initialAliases.getTexts(),
				removedAliases = [];

			for ( var i = 0; i < initialTexts.length; i++ ) {
				if ( currentTexts.indexOf( initialTexts[ i ] ) === -1 ) {
					removedAliases.push( initialTexts[ i ] );
				}
			}

			return removedAliases;
		}
	};

}( wikibase ) );

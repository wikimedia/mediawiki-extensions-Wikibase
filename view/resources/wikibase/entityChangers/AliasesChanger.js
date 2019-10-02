/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb ) {
	'use strict';

	var MODULE = wb.entityChangers,
		datamodel = require( 'wikibase.datamodel' );

	/**
	 * @constructor
	 *
	 * @param {wikibase.api.RepoApi} api
	 * @param {wikibase.RevisionStore} revisionStore
	 * @param {datamodel.Entity} entity
	 */
	var SELF = MODULE.AliasesChanger = function WbEntityChangersAliasesChanger( api, revisionStore, entity ) {
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
		 * @param {datamodel.MultiTerm} aliases
		 * @return {jQuery.Promise}
		 *         No resolved parameters.
		 *         Rejected parameters:
		 *         - {wikibase.api.RepoApiError}
		 */
		setAliases: function ( aliases ) {
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
			.done( function ( response ) {
				self._revisionStore.setAliasesRevision( response.entity.lastrevid );

				self._entity.getFingerprint().setAliases( language, aliases );

				var texts = [];
				if ( response.entity.aliases && response.entity.aliases[ language ] ) {
					texts = response.entity.aliases[ language ].map( function ( value ) {
						return value.value;
					} );
				}
				deferred.resolve( new datamodel.MultiTerm( language, texts ) );
			} )
			.fail( function ( errorCode, errorObject ) {
				deferred.reject( wb.api.RepoApiError.newFromApiResponse( errorObject, 'save' ) );
			} );

			return deferred.promise();
		},

		/**
		 * @param {string} language
		 * @return {datamodel.MultiTerm}
		 */
		_getInitialAliases: function ( language ) {
			return this._entity.getFingerprint().getAliasesFor( language )
				|| new datamodel.MultiTerm( language, [] );
		},

		/**
		 * @param {datamodel.MultiTerm} currentAliases
		 * @param {datamodel.MultiTerm} initialAliases
		 * @return {string[]}
		 */
		_getNewAliasesTexts: function ( currentAliases, initialAliases ) {
			var currentTexts = currentAliases.getTexts(),
				initialTexts = initialAliases.getTexts(),
				newAliases = [];

			for ( var i = 0; i < currentTexts.length; i++ ) {
				if ( initialTexts.indexOf( currentTexts[ i ] ) === -1 ) {
					newAliases.push( currentTexts[ i ] );
				}
			}

			return newAliases;
		},

		/**
		 * @param {datamodel.MultiTerm} currentAliases
		 * @param {datamodel.MultiTerm} initialAliases
		 * @return {string[]}
		 */
		_getRemovedAliasesTexts: function ( currentAliases, initialAliases ) {
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
	} );

}( wikibase ) );

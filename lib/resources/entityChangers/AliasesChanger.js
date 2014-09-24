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
	var SELF = MODULE.AliasesChanger = function( api, revisionStore, entity ) {
		this._api = api;
		this._revisionStore = revisionStore;
		this._entity = entity;
	};

	$.extend( SELF.prototype, {
		_entity: null,
		_revisionStore: null,
		_api: null,

		/**
		 * @param {Object[]} aliases
		 * @param {String} language
		 * @return {jQuery.Promise}
		 *         Resolved parameters:
		 *         - undefined
		 *         Rejected parameters:
		 *         - {wikibase.RepoApiError}
		 */
		setAliases: function( aliases, language ) {
			var deferred = $.Deferred();
			var self = this;

			this._api.setAliases(
				this._entity.getId(),
				this._revisionStore.getAliasesRevision(),
				this._getNewAliases( aliases, language ),
				this._getRemovedAliases( aliases, language ),
				language
			)
			.done( function( response ) {
				self._revisionStore.setAliasesRevision( response.entity.lastrevid );

				// FIXME: Maybe check API's return value?

				// FIXME: Introduce setter, get this right
				self._entity._data.aliases = self._entity._data.aliases || {};
				self._entity._data.aliases[ language ] = aliases;

				deferred.resolve();
			} )
			.fail( function( errorCode, errorObject ) {
				deferred.reject( wb.RepoApiError.newFromApiResponse( errorObject, 'save' ) );
			} );

			return deferred.promise();
		},

		/**
		 * @return {string[]}
		 */
		_getNewAliases: function( currentAliases, language ) {
			var initialAliases = this._entity.getAliases( language ) || [],
				newAliases = [];

			for( var i = 0; i < currentAliases.length; i++ ) {
				if( $.inArray( currentAliases[i], initialAliases ) === -1 ) {
					newAliases.push( currentAliases[i] );
				}
			}

			return newAliases;
		},

		/**
		 * @return {string[]}
		 */
		_getRemovedAliases: function( currentAliases, language ) {
			var initialAliases = this._entity.getAliases( language ) || [],
				removedAliases = [];

			for( var i = 0; i < initialAliases.length; i++ ) {
				if( $.inArray( initialAliases[i], currentAliases ) === -1 ) {
					removedAliases.push( initialAliases[i] );
				}
			}

			return removedAliases;
		},

	} );
} ( wikibase, jQuery ) );

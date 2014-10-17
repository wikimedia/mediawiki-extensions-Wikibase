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
var SELF = MODULE.AliasesChanger = function( api, revisionStore, entity ) {
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
	 * @param {Object[]} aliases
	 * @param {string} language
	 * @return {jQuery.Promise}
	 *         No resolved parameters.
	 *         Rejected parameters:
	 *         - {wikibase.RepoApiError}
	 */
	setAliases: function( aliases, language ) {
		var deferred = $.Deferred(),
			self = this,
			initialAliases = this._getInitialAliases( language );

		this._api.setAliases(
			this._entity.getId(),
			this._revisionStore.getAliasesRevision(),
			this._getNewAliases( aliases, initialAliases ),
			this._getRemovedAliases( aliases, initialAliases ),
			language
		)
		.done( function( response ) {
			self._revisionStore.setAliasesRevision( response.entity.lastrevid );

			self._entity.getFingerprint().setAliases(
				language,
				new wb.datamodel.MultiTerm( language, aliases )
			);

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
	_getInitialAliases: function( language ) {
		var aliases = this._entity.getFingerprint().getAliasesFor( language );
		return aliases ? aliases.getTexts() : [];
	},

	/**
	 * @param {string[]} currentAliases
	 * @param {string[]} initialAliases
	 * @return {string[]}
	 */
	_getNewAliases: function( currentAliases, initialAliases ) {
		var newAliases = [];

		for( var i = 0; i < currentAliases.length; i++ ) {
			if( $.inArray( currentAliases[i], initialAliases ) === -1 ) {
				newAliases.push( currentAliases[i] );
			}
		}

		return newAliases;
	},

	/**
	 * @param {string[]} currentAliases
	 * @param {string[]} initialAliases
	 * @return {string[]}
	 */
	_getRemovedAliases: function( currentAliases, initialAliases ) {
		var removedAliases = [];

		for( var i = 0; i < initialAliases.length; i++ ) {
			if( $.inArray( initialAliases[i], currentAliases ) === -1 ) {
				removedAliases.push( initialAliases[i] );
			}
		}

		return removedAliases;
	}
} );

} ( wikibase, jQuery ) );

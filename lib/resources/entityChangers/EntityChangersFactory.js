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
var SELF = MODULE.EntityChangersFactory = function( api, revisionStore, entity ) {
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
	 * @return {wikibase.entityChangers.AliasesChanger}
	 */
	getAliasesChanger: function() {
		return new MODULE.AliasesChanger( this._api, this._revisionStore, this._entity );
	},

	/**
	 * @return {wikibase.entityChangers.ClaimsChanger}
	 */
	getClaimsChanger: function() {
		return new MODULE.ClaimsChanger( this._api, this._revisionStore, this._entity );
	},

	/**
	 * @return {wikibase.entityChangers.DescriptionsChanger}
	 */
	getDescriptionsChanger: function() {
		return new MODULE.DescriptionsChanger( this._api, this._revisionStore, this._entity );
	},

	/**
	 * @return {wikibase.entityChangers.ReferencesChanger}
	 */
	getReferencesChanger: function() {
		return new MODULE.ReferencesChanger( this._api, this._revisionStore, this._entity );
	}
} );

}( wikibase, jQuery ) );

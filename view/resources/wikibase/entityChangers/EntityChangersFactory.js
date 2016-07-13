/**
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function( wb, $ ) {
	'use strict';

var MODULE = wb.entityChangers;

/**
 * @constructor
 * @since 0.5
 *
 * @param {wikibase.api.RepoApi} api
 * @param {wikibase.RevisionStore} revisionStore
 * @param {wikibase.datamodel.Entity} entity
 */
var SELF = MODULE.EntityChangersFactory = function WbEntityChangersEntityChangersFactory( api, revisionStore, entity ) {
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
	 * @return {wikibase.entityChangers.AliasesChanger}
	 */
	getAliasesChanger: function() {
		return new MODULE.AliasesChanger( this._api, this._revisionStore, this._entity );
	},

	/**
	 * @return {wikibase.entityChangers.StatementsChanger}
	 */
	getStatementsChanger: function() {
		return new MODULE.StatementsChanger(
			this._api,
			this._revisionStore,
			this._entity,
			new wb.serialization.StatementSerializer(),
			new wb.serialization.StatementDeserializer()
		);
	},

	/**
	 * @return {wikibase.entityChangers.DescriptionsChanger}
	 */
	getDescriptionsChanger: function() {
		return new MODULE.DescriptionsChanger( this._api, this._revisionStore, this._entity );
	},

	/**
	 * @return {wikibase.entityChangers.EntityTermsChanger}
	 */
	getEntityTermsChanger: function() {
		return new MODULE.EntityTermsChanger( this._api, this._revisionStore, this._entity );
	},

	/**
	 * @return {wikibase.entityChangers.LabelsChanger}
	 */
	getLabelsChanger: function() {
		return new MODULE.LabelsChanger( this._api, this._revisionStore, this._entity );
	},

	/**
	 * @return {wikibase.entityChangers.ReferencesChanger}
	 */
	getReferencesChanger: function() {
		return new MODULE.ReferencesChanger(
			this._api,
			this._revisionStore,
			this._entity,
			new wb.serialization.ReferenceSerializer(),
			new wb.serialization.ReferenceDeserializer()
		);
	},

	/**
	 * @return {wikibase.entityChangers.SiteLinksChanger}
	 */
	getSiteLinksChanger: function() {
		return new MODULE.SiteLinksChanger( this._api, this._revisionStore, this._entity );
	}
} );

}( wikibase, jQuery ) );

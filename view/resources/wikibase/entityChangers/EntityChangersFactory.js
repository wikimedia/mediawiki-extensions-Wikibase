/**
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb, $ ) {
	'use strict';

	var MODULE = wb.entityChangers;

	/**
	 * @constructor
	 *
	 * @param {wikibase.api.RepoApi} api
	 * @param {wikibase.RevisionStore} revisionStore
	 * @param {wikibase.datamodel.Entity} entity
	 * @param {Function} [fireHook] optional callback that is triggered on certain events, called with the hook name (string) as first argument and hook arguments as remaining arguments.
	 */
	var SELF = MODULE.EntityChangersFactory = function WbEntityChangersEntityChangersFactory(
		api,
		revisionStore,
		entity,
		fireHook
	) {
		this._api = api;
		this._revisionStore = revisionStore;
		this._entity = entity;
		this._fireHook = fireHook;
	};

	$.extend( SELF.prototype, {
		/**
		 * @type {wikibase.api.RepoApi}
		 */
		_api: null,

		/**
		 * @type {wikibase.RevisionStore}
		 */
		_revisionStore: null,

		/**
		 * @type {wikibase.datamodel.Entity}
		 */
		_entity: null,

		/**
		 * @type {Function}
		 */
		_fireHook: null,

		getRevisionStore: function () {
			return this._revisionStore;
		},

		getEntity: function () {
			return this._entity;
		},

		/**
		 * @return {wikibase.entityChangers.AliasesChanger}
		 */
		getAliasesChanger: function () {
			return new MODULE.AliasesChanger( this._api, this._revisionStore, this._entity );
		},

		/**
		 * @return {wikibase.entityChangers.StatementsChanger}
		 */
		getStatementsChanger: function () {
			return new MODULE.StatementsChanger(
				this._api,
				this._revisionStore,
				this._entity,
				new wb.serialization.StatementSerializer(),
				new wb.serialization.StatementDeserializer(),
				this._fireHook
			);
		},

		/**
		 * @return {wikibase.entityChangers.DescriptionsChanger}
		 */
		getDescriptionsChanger: function () {
			return new MODULE.DescriptionsChanger( this._api, this._revisionStore, this._entity );
		},

		/**
		 * @return {wikibase.entityChangers.EntityTermsChanger}
		 */
		getEntityTermsChanger: function () {
			return new MODULE.EntityTermsChanger( this._api, this._revisionStore, this._entity );
		},

		/**
		 * @return {wikibase.entityChangers.LabelsChanger}
		 */
		getLabelsChanger: function () {
			return new MODULE.LabelsChanger( this._api, this._revisionStore, this._entity );
		},

		/**
		 * @return {wikibase.entityChangers.SiteLinkSetsChanger}
		 */
		getSiteLinkSetsChanger: function () {
			return new MODULE.SiteLinkSetsChanger( this._api, this._revisionStore, this._entity );
		}
	} );

}( wikibase, jQuery ) );

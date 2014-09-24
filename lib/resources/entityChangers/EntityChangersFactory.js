/**
 * @licence GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */
( function( wb, $ ) {
	'use strict';

	var MODULE = wb.entityChangers;

	/**
	 * @param {wb.RepoApi}
	 * @param {wb.RevisionStore}
	 * @param {wb.datamodel.Entity}
	 */
	var SELF = MODULE.EntityChangersFactory = function( api, revisionStore, entity ) {
		this._api = api;
		this._revisionStore = revisionStore;
		this._entity = entity;
	};

	$.extend( SELF.prototype, {
		/**
		 * @type {wb.datamodel.Entity}
		 */
		_entity: null,

		/**
		 * @type {wb.RevisionStore}
		 */
		_revisionStore: null,

		/**
		 * @type {wb.RepoApi}
		 */
		_api: null,

		getAliasesChanger: function() {
			return new MODULE.AliasesChanger( this._api, this._revisionStore, this._entity );
		},

		getClaimsChanger: function() {
			return new MODULE.ClaimsChanger( this._api, this._revisionStore, this._entity );
		},

		getReferencesChanger: function() {
			return new MODULE.ReferencesChanger( this._api, this._revisionStore, this._entity );
		},
	} );
}( wikibase, jQuery ) );

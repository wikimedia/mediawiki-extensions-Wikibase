/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb ) {
	'use strict';

	var MODULE = wb.entityChangers,
		serialization = require( 'wikibase.serialization' );

	MODULE.EntityChangersFactory = class {
		/**
		 * @param {wikibase.api.RepoApi} api
		 * @param {wikibase.RevisionStore} revisionStore
		 * @param {wikibase.datamodel.Entity} entity
		 * @param {Function} [fireHook] optional callback that is triggered on certain events, called with the hook name (string) as first argument and hook arguments as remaining arguments.
		 */
		constructor(
			api,
			revisionStore,
			entity,
			fireHook
		) {
			/**
			 * @type {wikibase.api.RepoApi}
			 */
			this._api = api;
			/**
			 * @type {wikibase.RevisionStore}
			 */
			this._revisionStore = revisionStore;
			/**
			 * @type {wikibase.datamodel.Entity}
			 */
			this._entity = entity;
			/**
			 * @type {Function}
			 */
			this._fireHook = fireHook;
		}

		getRevisionStore() {
			return this._revisionStore;
		}

		getEntity() {
			return this._entity;
		}

		/**
		 * @return {wikibase.entityChangers.StatementsChanger}
		 */
		getStatementsChanger() {
			if ( typeof this._entity.getStatements !== 'function' ) {
				throw new Error( 'Statements Changer requires entity with statements' );
			}
			return new MODULE.StatementsChanger(
				this._api,
				this._revisionStore,
				new wb.entityChangers.StatementsChangerState( this._entity.getId(), this._entity.getStatements() ),
				new serialization.StatementSerializer(),
				new serialization.StatementDeserializer(),
				this._fireHook
			);
		}

		/**
		 * @return {wikibase.entityChangers.EntityTermsChanger}
		 */
		getEntityTermsChanger() {
			return new MODULE.EntityTermsChanger( this._api, this._revisionStore, this._entity );
		}

		/**
		 * @return {wikibase.entityChangers.SiteLinkSetsChanger}
		 */
		getSiteLinkSetsChanger() {
			return new MODULE.SiteLinkSetsChanger( this._api, this._revisionStore, this._entity );
		}
	};

}( wikibase ) );

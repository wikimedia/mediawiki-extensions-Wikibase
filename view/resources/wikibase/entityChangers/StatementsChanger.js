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
	 * @param {wikibase.entityChangers.StatementsChangerState} statementsChangerState
	 * @param {wikibase.serialization.StatementSerializer} statementSerializer
	 * @param {wikibase.serialization.StatementDeserializer} statementDeserializer
	 * @param {Function} [fireHook] called after a statement has been saved (wikibase.statement.saved) or deleted (wikibase.statement.deleted), with the hook name (wikibase.…), entity ID and statement ID as arguments.
	 */
	var SELF = MODULE.StatementsChanger = function WbEntityChangersStatementsChanger(
		api,
		revisionStore,
		statementsChangerState,
		statementSerializer,
		statementDeserializer,
		fireHook
	) {
		this._api = api;
		this._revisionStore = revisionStore;
		this._statementsChangerState = statementsChangerState;
		this._statementSerializer = statementSerializer;
		this._statementDeserializer = statementDeserializer;
		this._fireHook = fireHook || function () {
		};
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
		 * @type {wikibase.entityChangers.StatementsChangerState}
		 */
		_statementsChangerState: null,

		/**
		 * @type {wikibase.serialization.StatementSerializer}
		 */
		_statementSerializer: null,

		/**
		 * @type {wikibase.serialization.StatementDeserializer}
		 */
		_statementDeserializer: null,

		/**
		 * @type {Function}
		 */
		_fireHook: null,

		/**
		 * @param {datamodel.Statement} statement
		 * @return {jQuery.Promise}
		 *         No resolved parameters.
		 *         Rejected parameters:
		 *         - {wikibase.api.RepoApiError}
		 */
		remove: function ( statement ) {
			var deferred = $.Deferred(),
				self = this,
				guid = statement.getClaim().getGuid();

			this._api.removeClaim( guid, this._revisionStore.getClaimRevision( guid ) )
			.done( function ( response ) {
				var propertyId = statement.getClaim().getMainSnak().getPropertyId();

				self._revisionStore.setClaimRevision( response.pageinfo.lastrevid, guid );

				deferred.resolve();

				self._fireHook(
					'wikibase.statement.removed',
					self._statementsChangerState.getEntityId(),
					guid
				);

				self._updateChangerStateOnRemoval( propertyId, guid );
			} )
			.fail( function ( errorCode, error ) {
				deferred.reject( wb.api.RepoApiError.newFromApiResponse( error, 'remove' ) );
			} );

			return deferred.promise();
		},

		/**
		 * @param {string} propertyId
		 * @param {string} guid
		 * @private
		 */
		_updateChangerStateOnRemoval: function ( propertyId, guid ) {
			var statementsForPropertyId, statementsForPropertyIdArray;

			statementsForPropertyId = this._statementsChangerState.getStatements().getItemByKey( propertyId );
			if ( statementsForPropertyId === null ) {
				// Removed a statement we don't know… warn?
				return;
			}

			statementsForPropertyId = statementsForPropertyId.getItemContainer();
			statementsForPropertyIdArray = statementsForPropertyId.toArray();
			for ( var i in statementsForPropertyIdArray ) {
				if ( statementsForPropertyIdArray[ i ].getClaim().getGuid() === guid ) {
					statementsForPropertyId.removeItem( statementsForPropertyIdArray[ i ] );
					break;
				}
			}
			if ( statementsForPropertyId.isEmpty() ) {
				// No more statements with this Property id, remove the whole thing.
				this._statementsChangerState.getStatements().removeItemByKey( propertyId );
			}
		},

		/**
		 * @param {datamodel.Statement} statement
		 * @return {Object} jQuery.Promise
		 *         Resolved parameters:
		 *         - {datamodel.Statement} The saved statement
		 *         Rejected parameters:
		 *         - {wikibase.api.RepoApiError}
		 */
		save: function ( statement ) {
			var self = this,
				deferred = $.Deferred();

			this._api.setClaim(
				this._statementSerializer.serialize( statement ),
				this._revisionStore.getClaimRevision( statement.getClaim().getGuid() )
			)
			.done( function ( result ) {
				var savedStatement = self._statementDeserializer.deserialize( result.claim ),
					guid = savedStatement.getClaim().getGuid(),
					propertyId = statement.getClaim().getMainSnak().getPropertyId(),
					pageInfo = result.pageinfo,
					oldStatement = null;
				var statementsForPropertyId = self._statementsChangerState.getStatements().getItemByKey( propertyId );

				if ( statementsForPropertyId !== null ) {

					statementsForPropertyId = statementsForPropertyId.getItemContainer();
					var statementsForPropertyIdArray = statementsForPropertyId.toArray();

					for ( var i in statementsForPropertyIdArray ) {
						if ( statementsForPropertyIdArray[ i ].getClaim().getGuid() === guid ) {
							oldStatement = statementsForPropertyIdArray[ i ];
							break;
						}
					}
				}

				// Update revision store:
				self._revisionStore.setClaimRevision( pageInfo.lastrevid, guid );

				deferred.resolve( savedStatement );

				self._fireHook(
					'wikibase.statement.saved',
					self._statementsChangerState.getEntityId(),
					guid,
					oldStatement,
					savedStatement
				);

				self._updateChangerStateOnSetClaim( savedStatement, propertyId, guid );
			} )
			.fail( function ( errorCode, error ) {
				deferred.reject( wb.api.RepoApiError.newFromApiResponse( error, 'save' ) );
			} );

			return deferred.promise();
		},

		/**
		 * @param {datamodel.Statement} statement
		 * @param {string} propertyId
		 * @param {string} guid
		 * @private
		 */
		_updateChangerStateOnSetClaim: function ( statement, propertyId, guid ) {
			var statementsForPropertyId = this._statementsChangerState.getStatements().getItemByKey( propertyId ),
				statementsForPropertyIdArray;

			if ( statementsForPropertyId ) {
				statementsForPropertyIdArray = statementsForPropertyId.getItemContainer().toArray();
				for ( var i in statementsForPropertyIdArray ) {
					if ( statementsForPropertyIdArray[ i ].getClaim().getGuid() === guid ) {
						// Remove (the new statement will be re-added)
						statementsForPropertyId.removeItem( statementsForPropertyIdArray[ i ] );
						break;
					}
				}
			} else {
				// No statement with this property id yet, start a new group
				this._statementsChangerState.getStatements().addItem(
					new datamodel.StatementGroup( propertyId, new datamodel.StatementList() )
				);
				statementsForPropertyId = this._statementsChangerState.getStatements().getItemByKey( propertyId );
			}
			statementsForPropertyId.addItem( statement );
		}
	} );

}( wikibase ) );

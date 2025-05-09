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
	MODULE.StatementsChanger = class {
		constructor(
			api,
			revisionStore,
			statementsChangerState,
			statementSerializer,
			statementDeserializer,
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
			 * @type {wikibase.entityChangers.StatementsChangerState}
			 */
			this._statementsChangerState = statementsChangerState;
			/**
			 * @type {wikibase.serialization.StatementSerializer}
			 */
			this._statementSerializer = statementSerializer;
			/**
			 * @type {wikibase.serialization.StatementDeserializer}
			 */
			this._statementDeserializer = statementDeserializer;
			/**
			 * @type {Function}
			 */
			this._fireHook = fireHook || function () {
			};
		}

		/**
		 * @param {datamodel.Statement} statement
		 * @return {jQuery.Promise}
		 *         Resolved parameters:
		 *         - {datamodel.ValueChangeResult} A ValueChangeResult with a null value (since this is a remove) and
		 *           details of a temp user, if one is created. Consistent with the `save` resolve semantics
		 *         Rejected parameters:
		 *         - {wikibase.api.RepoApiError}
		 */
		remove( statement ) {
			var deferred = $.Deferred(),
				self = this,
				guid = statement.getClaim().getGuid();

			this._api.removeClaim( guid, this._revisionStore.getClaimRevision( guid ) )
			.done( ( response ) => {
				var propertyId = statement.getClaim().getMainSnak().getPropertyId();

				self._revisionStore.setClaimRevision( response.pageinfo.lastrevid, guid );

				const tempUserWatcher = new MODULE.TempUserWatcher();
				const valueChangeResult = new MODULE.ValueChangeResult( null, tempUserWatcher );
				tempUserWatcher.processApiResult( response );
				deferred.resolve( valueChangeResult );

				self._fireHook(
					'wikibase.statement.removed',
					self._statementsChangerState.getEntityId(),
					guid
				);

				self._updateChangerStateOnRemoval( propertyId, guid );
			} )
			.fail( ( errorCode, error ) => {
				deferred.reject( wb.api.RepoApiError.newFromApiResponse( error, 'remove' ) );
			} );

			return deferred.promise();
		}

		/**
		 * @param {string} propertyId
		 * @param {string} guid
		 * @private
		 */
		_updateChangerStateOnRemoval( propertyId, guid ) {
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
		}

		/**
		 * @param {datamodel.Statement} statement
		 * @return {Object} jQuery.Promise
		 *         Resolved parameters:
		 *         - {datamodel.ValueChangeResult} A ValueChangeResult wrapping the saved datamodel.Statement
		 *         Rejected parameters:
		 *         - {wikibase.api.RepoApiError}
		 */
		save( statement ) {
			var self = this,
				deferred = $.Deferred();

			this._api.setClaim(
				this._statementSerializer.serialize( statement ),
				this._revisionStore.getClaimRevision( statement.getClaim().getGuid() )
			)
			.done( ( result ) => {
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

				// Handle TempUser if one is created
				var tempUserWatcher = new MODULE.TempUserWatcher();
				tempUserWatcher.processApiResult( result );
				deferred.resolve( new MODULE.ValueChangeResult( savedStatement, tempUserWatcher ) );

				self._fireHook(
					'wikibase.statement.saved',
					self._statementsChangerState.getEntityId(),
					guid,
					oldStatement,
					savedStatement
				);

				self._updateChangerStateOnSetClaim( savedStatement, propertyId, guid );
			} )
			.fail( ( errorCode, error ) => {
				deferred.reject( wb.api.RepoApiError.newFromApiResponse( error, 'save' ) );
			} );

			return deferred.promise();
		}

		/**
		 * @param {datamodel.Statement} statement
		 * @param {string} propertyId
		 * @param {string} guid
		 * @private
		 */
		_updateChangerStateOnSetClaim( statement, propertyId, guid ) {
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
	};

}( wikibase ) );

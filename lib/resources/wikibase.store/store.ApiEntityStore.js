/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
( function( wb, $ ) {
	'use strict';

	var MODULE = wb.store;

	/**
	 * Entity store fetching entities from API.
	 * @constructor
	 * @extends wikibase.store.EntityStore
	 * @since 0.5
	 *
	 * @param {wikibase.RepoApi} repoApi
	 * @param {wikibase.store.FetchedContentUnserializer} fetchedEntityUnserializer;
	 * @param {string[]} languages
	 */
	var SELF = MODULE.ApiEntityStore = util.inherit(
		'WbApiEntityStore',
		MODULE.EntityStore,
		function( repoApi, fetchedEntityUnserializer, languages ) {
			this._entities = {};
			this._fetchedEntityUnserializer = fetchedEntityUnserializer;
			this._languages = languages;
			this._repoApi = repoApi;
		}
	);

	$.extend( SELF.prototype, {
		/**
		 * @type {Object}
		 */
		_entities: null,

		/**
		 * @type {wikibase.store.FetchedContentUnserializer}
		 */
		_fetchedEntityUnserializer: null,

		/**
		 * @type {string[]}
		 */
		_languages: null,

		/**
		 * @type {wikibase.RepoApi}
		 */
		_repoApi: null,

		/**
		 * @see wikibase.store.EntityStore.getMultipleRaw
		 */
		getMultipleRaw: function( entityIds ) {
			var deferreds = $.map( entityIds, function() { return $.Deferred(); } );
			var self = this;
			var entityIdsToFetch = [];
			var entityIdToIndex = {};

			$.each( entityIds, function( i, entityId ) {
				if( self._entities.hasOwnProperty( entityId ) ) {
					deferreds[i].resolve( self._entities[ entityId ] );
				} else {
					entityIdsToFetch.push( entityId );
					entityIdToIndex[ entityId ] = i;
				}
			} );

			if( entityIdsToFetch.length > 0 ) {
				this._repoApi.getEntities( entityIdsToFetch, null, this._languages )
				.done( function( result ) {
					$.each( result.entities, function( id, entityData ) {
						if( entityData.missing === '' ) {
							return; // missing entity
						}

						var entity = self._fetchedEntityUnserializer.deserialize( {
							title: entityData.title,
							content: entityData
						} );
						self._entities[ entity.getContent().getId() ] = entity;
					} );

					$.each( entityIdsToFetch, function( i, entityId ) {
						deferreds[ entityIdToIndex[ entityId ] ].resolve( self._entities[ entityId ] );
					} );
				} )
				// FIXME: Evaluate failing promise
				.fail( function() {
					$.each( entityIdsToFetch, function( i, entityId ) {
						deferreds[ entityIdToIndex[ entityId ] ].reject();
					} );
				} );
			}

			return $.map( deferreds, function( deferred ) {
				return deferred.promise();
			} );
		}
	} );
}( wikibase, jQuery ) );


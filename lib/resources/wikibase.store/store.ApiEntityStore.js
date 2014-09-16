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
		 * @see wikibase.store.Entity.store.get
		 */
		get: function( entityId ) {
			var deferred = $.Deferred();
			var self = this;

			if( this._entities.hasOwnProperty( entityId ) ) {
				deferred.resolve( this._entities[ entityId ] );
			} else {
				this._repoApi.getEntities( entityId, null, this._languages )
				.done( function( result ) {
					$.each( result.entities, function( id, entityData ) {
						if( entityData.missing === '' ) {
							return; // missing entity
						}

						var entity = self._fetchedEntityUnserializer.unserialize( {
							title: entityData.title,
							content: entityData
						} );
						self._entities[ entity.getContent().getId() ] = entity;
					} );

					deferred.resolve( self._entities[ entityId ] );
				} )
				// FIXME: Evaluate failing promise
				.fail( deferred.reject );
			}

			return deferred.promise();
		}
	} );
}( wikibase, jQuery ) );


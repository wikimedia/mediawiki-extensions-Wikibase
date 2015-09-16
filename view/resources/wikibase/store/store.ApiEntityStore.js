/**
 * @licence GNU GPL v2+
 * @author Adrian Heine < adrian.heine@wikimedia.de >
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
	 * @param {wikibase.api.RepoApi} repoApi
	 * @param {wikibase.store.FetchedContentUnserializer} fetchedEntityUnserializer;
	 * @param {string[]} languages
	 */
	MODULE.ApiEntityStore = util.inherit(
		'WbApiEntityStore',
		MODULE.EntityStore,
		function( repoApi, fetchedEntityUnserializer, languages ) {
			this._fetchedEntityUnserializer = fetchedEntityUnserializer;
			this._languages = languages;
			this._repoApi = repoApi;
		},
	{

		/**
		 * @type {wikibase.store.FetchedContentUnserializer}
		 */
		_fetchedEntityUnserializer: null,

		/**
		 * @type {string[]}
		 */
		_languages: null,

		/**
		 * @type {wikibase.api.RepoApi}
		 */
		_repoApi: null,

		/**
		 * @see wikibase.store.EntityStore.get
		 */
		get: function( entityId ) {
			var deferred = $.Deferred();
				self = this,

			this._repoApi.getEntities( [ entityId ], null, this._languages )
			.done( function( result ) {
				var entityData = result.entities[ entityId ];
				var entity = null,

				if( entityData.missing !== '' ) {
					entity = self._fetchedEntityUnserializer.deserialize( {
						title: entityData.title,
						content: entityData
					} );
				}

				deferred.resolve( entity );
			} )
			// FIXME: Evaluate failing promise
			.fail( function() {
				deferred.reject();
			} );

			return deferred.promise();
		}
	} );
}( wikibase, jQuery ) );

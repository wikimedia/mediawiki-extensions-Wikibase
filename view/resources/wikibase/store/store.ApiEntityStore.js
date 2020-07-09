/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function () {
	'use strict';

	var EntityStore = require( './store.EntityStore.js' );

	/**
	 * Entity store fetching entities from API.
	 *
	 * @constructor
	 * @extends EntityStore
	 *
	 * @param {wikibase.api.RepoApi} repoApi
	 * @param {wikibase.serialization.EntityDeserializer} entityDeserializer
	 * @param {string[]} languages
	 */
	module.exports = util.inherit(
		'WbApiEntityStore',
		EntityStore,
		function ( repoApi, entityDeserializer, languages ) {
			this._entityDeserializer = entityDeserializer;
			this._languages = languages;
			this._repoApi = repoApi;
		},
		{

			/**
			 * @type {wikibase.serialization.EntityDeserializer}
			 */
			_entityDeserializer: null,

			/**
			 * @type {string[]}
			 */
			_languages: null,

			/**
			 * @type {wikibase.api.RepoApi}
			 */
			_repoApi: null,

			/**
			 * @see EntityStore.get
			 */
			get: function ( entityId ) {
				var deferred = $.Deferred(),
					self = this;

				this._repoApi.getEntities( [ entityId ], null, this._languages )
				.done( function ( result ) {
					var entityData = result.entities[ entityId ];
					var entity = null;

					if ( entityData.missing !== '' ) {
						entity = self._entityDeserializer.deserialize( entityData );
					}

					deferred.resolve( entity );
				} )
				// FIXME: Evaluate failing promise
				.fail( function () {
					deferred.reject();
				} );

				return deferred.promise();
			}
		} );
}() );

/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
( function( wb, $ ) {
	'use strict';

	var MODULE = wb.store;

	/**
	 * Entity store fetching entities from API
	 * @constructor
	 * @since 0.5
	 *
	 * @param {wb.RepoApi} repoApi
	 */
	var SELF = MODULE.ApiEntityStore = util.inherit('WbApiEntityStore', MODULE.EntityStore, function( repoApi ) {
	  this._repoApi = repoApi;
		this._entities = {};
	} );

	$.extend( SELF.prototype, {
		/**
		 * @type {wb.RepoApi}
		 */
		_repoApi: null,

		/**
		 * @type {Object}
		 */
		_entities: null,

		get: function( entityId ) {
			var deferred = $.Deferred();
			var self = this;

			if( this._entities.hasOwnProperty( entityId ) ) {
				deferred.resolve( this._entities[ entityId ] );
			} else {
				this._repoApi.getEntities( entityId, null, this._languages ).done( function( result ) {
					$.each( result.entities, function( id, entityData ) {
						if( entityData.missing === '' ) {
							return; // missing entity
						}

						var entity = this._repoApi.unserialize( {
							title: entityData.title,
							content: entityData
						} );
						self._entities[ entity.getId() ] = entity;
					} );

					deferred.resolve( self._entities[ entityId ] );
				} ).fail( deferred.reject );
			}

			return deferred.promise();
		}
	} );
}( wikibase, jQuery ) );


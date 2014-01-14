/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
( function( wb, $, mw ) {
	'use strict';

	var MODULE = wb.store;

	/**
	 * Entity store managing wikibase.Entity objects.
	 * @constructor
	 * @since 0.5
	 */
	var SELF = MODULE.EntityStore = function WbEntityStore() {
		this._entities = {};
	};

	$.extend( SELF.prototype, {
		/**
		 * Object containing wikibase.store.FetchedContent objects indexed by entity id.
		 * @tpye {Object}
		 */
		_entities: null,

		/**
		 * Returns a promise resolving to the entity, undefined or null
		 * @since 0.5
		 *
		 * @param {string} entityId
		 *
		 * @return {jQuery.Promise} Resolved parameters:
		 *                          - {wikibase.store.FetchedContent|undefined|null}
		 */
		get: function( entityId ) {
			var store = this,
				deferred = new $.Deferred();

			if( !entityId ) {
				// FIXME: This should probably be fixed on the caller's side
				deferred.resolve( null );
			} else if( this._entities.hasOwnProperty( entityId ) ) {
				// Caller should not assume synchronous behaviour:
				window.setTimeout( function() {
					deferred.resolve( store._entities[entityId] );
				}, 0 );
			} else {
				var abstractedApi = new wb.AbstractedRepoApi(),
					language = mw.config.get( 'wgUserLanguage' );

				abstractedApi.getEntities( entityId, null, [language] ).done( function( entities ) {
					var entity = entities[entityId];

					if( entity ) {
						store._entities[entityId] = new wb.store.FetchedContent( {
							// FIXME: Accessing _data is not ok
							title: new mw.Title( entity._data.title ),
							content: entity
						} );
					}

					deferred.resolve( store._entities[entityId] );
				} );
			}

			return deferred.promise();
		},

		/**
		 * Adds a batch of entities to the store.
		 * @since 0.5
		 *
		 * @param {Object} indexedEntities
		 */
		compile: function( indexedEntities ) {
			$.extend( this._entities, indexedEntities );
		}
	} );
}( wikibase, jQuery, mediaWiki ) );

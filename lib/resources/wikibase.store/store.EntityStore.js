/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
( function( wb, $ ) {
	'use strict';

	var MODULE = wb.store;

	/**
	 * @constructor
	 */
	var SELF = MODULE.EntityStore = function WbEntityStore() {
		this._entities = {};
	};

	$.extend( SELF.prototype, {
		/**
		 * Returns a promise resolving to the entity or undefined or null
		 *
		 * @param string entityId
		 *
		 * @return {jQuery.Promise}
		 */
		get: function( entityId ) {
			var store = this;
			var deferred = new $.Deferred();
			if( !entityId ) {
				// FIXME: This should probably be fixed on the caller's side
				deferred.resolve( null );
			} else if( this._entities.hasOwnProperty( entityId ) ) {
				window.setTimeout(function () {
					deferred.resolve( store._entities[entityId] );
				}, 0);
			} else {
				var abstractedApi = new wb.AbstractedRepoApi(),
					language = mw.config.get( 'wgUserLanguage' );

				abstractedApi.getEntities( entityId, null, [ language ] ).done( function( entities ) {
					var entity = entities[ entityId ];

					if( entity ) {
						store._entities[ entityId ] = new wb.store.FetchedContent( {
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
		 * Adds a batch of entities to the store
		 *
		 * @param {Object} data
		 */
		seed: function( data ) {
			$.extend( this._entities, data );
		}
	} );
}( wikibase, jQuery ) );

/**
 * @licence GNU GPL v2+
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
( function( wb, $ ) {
	'use strict';

	var MODULE = wb.store;

	/**
	 * An `EntityStore` decorator, adding a cache.
	 *
	 * @constructor
	 * @extends wikibase.store.EntityStore
	 * @since 0.5
	 *
	 * @param {wikibase.store.EntityStore} store
	 */
	MODULE.CachingEntityStore = util.inherit(
		'WbCachingEntityStore',
		wb.store.EntityStore,
		function( store ) {
			this._deferreds = {};
			this._store = store;
		},
	{
		/**
		 * @type {Object}
		 */
		_deferreds: null,

		/**
		 * @type {wikibase.store.EntityStore}
		 */
		_store: null,

		/**
		 * @see wikibase.store.EntityStore.getMultipleRaw
		 */
		getMultipleRaw: function( entityIds ) {
			var deferreds = [],
				self = this,
				entityIdsToFetch = [],
				entityIdToIndex = {};

			$.each( entityIds, function( i, entityId ) {
				if( self._deferreds.hasOwnProperty( entityId ) ) {
					deferreds[i] = self._deferreds[ entityId ];
				} else {
					entityIdsToFetch.push( entityId );
					entityIdToIndex[ entityId ] = i;
				}
			} );

			if( entityIdsToFetch.length > 0 ) {
				$.each( this._store.getMultipleRaw( entityIdsToFetch ), function( idx, promise ) {
					deferreds[ entityIdToIndex[ entityIdsToFetch[ idx ] ] ] = promise;
					self._deferreds[ entityIdsToFetch[ idx ] ] = promise;
				} );
			}

			return $.map( deferreds, function( deferred ) {
				return deferred.promise();
			} );
		}
	} );
}( wikibase, jQuery ) );

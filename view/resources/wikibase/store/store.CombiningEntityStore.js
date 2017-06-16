/**
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb, $ ) {
	'use strict';

	var MODULE = wb.store;

	/**
	 * Passes item to the handlers one after the other until it has a resolution
	 */
	function asyncFallback( item, handlers ) {
		var deferred = $.Deferred(),
			index = 0;

		function tryNext() {
			if ( handlers.length <= index ) {
				deferred.reject();
				return;
			}
			handlers[ index++ ]( item )
			.fail( tryNext )
			.done( deferred.resolve );
		}

		window.setTimeout( tryNext, 0 );

		return deferred.promise();
	}

	/**
	 * Entity store wrapping multiple EntityStore instances.
	 * @constructor
	 * @extends wikibase.store.EntityStore
	 *
	 * @param {Object[]} stores
	 */
	MODULE.CombiningEntityStore = util.inherit(
		'WbCombiningEntityStore',
		wb.store.EntityStore,
		function ( stores ) {
			this._stores = stores;
		},
		{
			/**
			 * @type {Object[]}
			 */
			_stores: null,

			/**
			 * @see wikibase.store.EntityStore.get
			 */
			get: function ( entityId ) {
				return asyncFallback( entityId, $.map( this._stores, function ( store ) {
					return function ( entityId ) {
						return store.get( entityId );
					};
				} ) );
			}
		} );
}( wikibase, jQuery ) );

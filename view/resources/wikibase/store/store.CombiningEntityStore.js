/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function () {
	'use strict';

	var EntityStore = require( './store.EntityStore.js' );

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
	 *
	 * @constructor
	 * @extends EntityStore
	 *
	 * @param {Object[]} stores
	 */
	module.exports = util.inherit(
		'WbCombiningEntityStore',
		EntityStore,
		function ( stores ) {
			this._stores = stores;
		},
		{
			/**
			 * @type {Object[]}
			 */
			_stores: null,

			/**
			 * @see EntityStore.get
			 */
			get: function ( entityId ) {
				return asyncFallback( entityId, this._stores.map( function ( store ) {
					return function ( id ) {
						return store.get( id );
					};
				} ) );
			}
		} );
}() );

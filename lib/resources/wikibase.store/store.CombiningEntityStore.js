/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
( function( wb, $ ) {
	'use strict';

	var MODULE = wb.store;

	/**
	 * Sequentially tries a handler on an array until a call succeeds.
	 *
	 * @param {*[]} arr
	 * @param {Function} elemHandler A function taking the values from arr one by one and returning
	 *        a jQuery.Promise.
	 * @return {jQuery.Promise}
	 */
	function asyncFirst( arr, elemHandler ) {
		var deferred = $.Deferred();
		var idx = 0;
		function tryNext() {
			if( arr.length <= idx ) {
				deferred.reject();
				return;
			}
			elemHandler( arr[ idx++ ] ).done( deferred.resolve ).fail( tryNext );
		}

		window.setTimeout( tryNext, 0 );

		return deferred.promise();
	}

	/**
	 * Entity store wrapping multiple EntityStore instances.
	 * @constructor
	 * @extends wikibase.store.EntityStore
	 * @since 0.5
	 *
	 * @param {Object[]} stores
	 */
	MODULE.CombiningEntityStore = util.inherit(
		'WbCombiningEntityStore',
		wb.store.EntityStore,
		function( stores ) {
			this._stores = stores;
		},
	{
		/**
		 * @type {Object[]}
		 */
		_stores: null,

		/**
		 * @see wikibase.store.Entity.store.get
		 */
		get: function( entityId ) {
			return asyncFirst( this._stores, function( getter ) {
				return getter.get( entityId );
			} );
		}
	} );
}( wikibase, jQuery ) );

/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
( function( wb, $ ) {
	'use strict';

	var MODULE = wb.store;

	/**
	 * Sequentially try a handler on an array until a call succeeds
	 *
	 * @param {mixed[]} arr
	 * @param {Function} elemHandler A function taking the values from arr one by one
	 * and returning a jQuery.Promise
	 * @return {jQuery.Promise}
	 */
	function asyncFirst( arr, elemHander ) {
		var deferred = $.Deferred();
		var idx = 0;
		function tryNext() {
			if( arr.length <= idx ) {
				deferred.reject();
				return;
			}
			elemHandler( arr[ idx++ ] ).then( deferred.resolve ).fail( tryNext );
		}

		window.setTimeout( tryNext, 0 );

		return deferred.promise();
	}

	/**
	 * Entity store managing wb.datamodel.Entity objects.
	 * @constructor
	 * @since 0.5
	 *
	 * @param {Object[]} stores
	 */
	var SELF = MODULE.CombiningEntityStore = util.inherit('WbCombiningEntityStore', wb.store.EntityStore, function( stores ) {
		this._stores = stores;
	} );

	$.extend( SELF.prototype, {
		/**
		 * @type {Object[]}
		 */
		_stores: null,

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
			if( !entityId ) {
				// FIXME: This should probably be fixed on the caller's side
				return $.Deferred().resolve( null );
			} else {
				return asyncFirst( this._stores, function( getter ) {
					return getter.get( entityId );
				} );
			}
		}
	} );
}( wikibase, jQuery ) );

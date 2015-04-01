/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
( function( wb, $ ) {
	'use strict';

	var MODULE = wb.store;

	/**
	 * Like jQuery.when, but does not differentiate between fail and success;
	 * it just waits for all promises to resolve one way or the other and then
	 * resolves itself.
	 */
	function whenFinished( arr ) {
		var deferred = $.Deferred(),
			returnsExpected = arr.length;

		$.each( arr, function( i, promise ) {
			promise.always( function() {
				if( --returnsExpected <= 0 ) {
					deferred.resolve();
				}
			} );
		} );

		return deferred.promise();
	}

	/**
	 * An asynchronous reduce which fails when the first handler fails
	 */
	function asyncReduce( arr, callback, initialValue ) {
		var deferred = $.Deferred(),
			index = 0,
			previousValue = initialValue;

		function tryNext() {
			if( arr.length <= index ) {
				deferred.resolve( previousValue );
				return;
			}
			callback( previousValue, arr[ index++ ] ).fail( deferred.reject )
			.done( function( newState ) {
				previousValue = newState;
				tryNext();
			} );
		}

		window.setTimeout( tryNext, 0 );

		return deferred.promise();
	}

	/**
	 * Passes all items from arr to the handlers one after the other until it has a resolution
	 * for every item
	 */
	function asyncMapFallback( arr, arrHandlers ) {
		var deferreds = $.map( arr, function() { return $.Deferred(); } );
		asyncReduce( arrHandlers, function( state, arrHandler ) {
			var deferred = $.Deferred();
			if( state.unresolvedArr.length === 0 ) {
				return deferred.reject( state ).promise();
			}

			var nextUnresolvedArr = [];
			var nextUnresolvedDeferreds = [];
			var promises = arrHandler( state.unresolvedArr );
			$.each( promises, function( i, promise ) {
				promise.done( state.unresolvedDeferreds[ i ].resolve )
				.fail( function() {
					nextUnresolvedArr.push( state.unresolvedArr[ i ] );
					nextUnresolvedDeferreds.push( state.unresolvedDeferreds[ i ] );
				} );
			} );
			whenFinished( promises ).done( function() {
				deferred.resolve( {
					unresolvedArr: nextUnresolvedArr,
					unresolvedDeferreds: nextUnresolvedDeferreds
				} );
			} );

			return deferred.promise();
		}, {
			unresolvedArr: arr,
			unresolvedDeferreds: deferreds
		} );

		return $.map( deferreds, function( deferred ) {
			return deferred.promise();
		} );
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
		 * @see wikibase.store.EntityStore.getMultipleRaw
		 */
		getMultipleRaw: function( entityIds ) {
			return asyncMapFallback( entityIds, $.map( this._stores, function( store ) {
				return function( entityIds ) {
					return store.getMultipleRaw( entityIds );
				};
			} ) );
		}
	} );
}( wikibase, jQuery ) );

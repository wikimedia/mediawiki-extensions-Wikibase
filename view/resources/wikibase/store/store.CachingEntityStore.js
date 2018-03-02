/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb ) {
	'use strict';

	var MODULE = wb.store;

	/**
	 * An `EntityStore` decorator, adding a cache.
	 *
	 * @constructor
	 * @extends wikibase.store.EntityStore
	 *
	 * @param {wikibase.store.EntityStore} store
	 */
	MODULE.CachingEntityStore = util.inherit(
		'WbCachingEntityStore',
		wb.store.EntityStore,
		function ( store ) {
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
			 * @see wikibase.store.EntityStore.get
			 */
			get: function ( entityId ) {
				if ( !this._deferreds.hasOwnProperty( entityId ) ) {
					this._deferreds[ entityId ] = this._store.get( entityId );
				}
				return this._deferreds[ entityId ];
			}
		} );
}( wikibase ) );

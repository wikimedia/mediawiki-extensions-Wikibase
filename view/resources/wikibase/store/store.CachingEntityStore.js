/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function () {
	'use strict';

	var EntityStore = require( './store.EntityStore.js' );

	/**
	 * An `EntityStore` decorator, adding a cache.
	 *
	 * @constructor
	 * @extends EntityStore
	 *
	 * @param {EntityStore} store
	 */
	module.exports = util.inherit(
		'WbCachingEntityStore',
		EntityStore,
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
			 * @type {EntityStore}
			 */
			_store: null,

			/**
			 * @see EntityStore.get
			 */
			get: function ( entityId ) {
				if ( !Object.prototype.hasOwnProperty.call( this._deferreds, entityId ) ) {
					this._deferreds[ entityId ] = this._store.get( entityId );
				}
				return this._deferreds[ entityId ];
			}
		} );
}() );

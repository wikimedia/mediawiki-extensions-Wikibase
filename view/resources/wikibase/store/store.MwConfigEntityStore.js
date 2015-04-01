/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
( function( wb, $, mw ) {
	'use strict';

	var MODULE = wb.store;

	/**
	 * Entity store fetching Entity objects from mediaWiki config.
	 * @constructor
	 * @extends wikibase.store.EntityStore
	 * @since 0.5
	 *
	 * @param {wikibase.store.FetchedContentUnserializer} fetchedEntityUnserializer
	 */
	MODULE.MwConfigEntityStore = util.inherit(
		'WbMwConfigEntityStore',
		MODULE.EntityStore,
		function( fetchedEntityUnserializer ) {
			this._fetchedEntityUnserializer = fetchedEntityUnserializer;
			this._fetchedEntities = getSerializedFetchedEntitiesFromConfig( 'wbUsedEntities' );
		},
	{
		/**
		 * @type {wikibase.store.FetchedContentUnserializer}
		 */
		_fetchedEntityUnserializer: null,

		/**
		 * @type {Object}
		 */
		_fetchedEntities: null,

		/**
		 * @see wikibase.store.EntityStore.get
		 */
		get: function( entityId ) {
			var deferred = $.Deferred();

			if( !this._fetchedEntities.hasOwnProperty( entityId ) ) {
				deferred.reject();
			} else {
				if( !( this._fetchedEntities[entityId] instanceof MODULE.FetchedContent ) ) {
					this._fetchedEntities[entityId] = this._fetchedEntityUnserializer.deserialize(
						this._fetchedEntities[entityId]
					);
				}
				deferred.resolve( this._fetchedEntities[entityId] );
			}

			return deferred.promise();
		}
	} );

	/**
	 * Helper for getting the serialized fetched entities in object form out of mw.config.
	 *
	 * @param {string} varName mw.config var name
	 * @return {Object}
	 *
	 * @throws {Error} In case the given config variable name does not exist.
	 */
	function getSerializedFetchedEntitiesFromConfig( varName ) {
		if( !mw.config.exists( varName ) ) {
			throw new Error( 'Can not load data for wikibase.store.MwConfigEntityStore. '
				+ 'mw.config variable "' + varName + '" is not set.' );
		}
		return JSON.parse( mw.config.get( varName ) );
	}

}( wikibase, jQuery, mediaWiki ) );

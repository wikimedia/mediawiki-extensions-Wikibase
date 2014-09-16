/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
( function( wb, $, mw ) {
	'use strict';

	var MODULE = wb.store;

	/**
	 * Entity store fetching entities from mw config
	 * @constructor
	 * @since 0.5
	 *
	 * @param {wb.store.FetchedContentUnserializer} fetchedEntityUnserializer
	 */
	var SELF = MODULE.MwConfigEntityStore = util.inherit( 'WbMwConfigEntityStore', MODULE.EntityStore, function( fetchedEntityUnserializer ) {
		this._fetchedEntityUnserializer = fetchedEntityUnserializer;
		this._fetchedEntities = getSerializedFetchedEntitiesFromConfig( 'wbUsedEntities' );
	} );

	$.extend( SELF.prototype, {

		/**
		 * @type {wb.store.FetchedContentUnserializer}
		 */
		_fetchedEntityUnserializer: null,

		/**
		 * @type {Object}
		 */
		_fetchedEntities: null,

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
			var deferred = $.Deferred();

			if( !this._fetchedEntities.hasOwnProperty( entityId ) ) {
				deferred.reject();
			} else {
				if( !( this._fetchedEntities[entityId] instanceof wb.store.FetchedContent ) ) {
					this._fetchedEntities[entityId] = this._fetchedEntityUnserializer.unserialize(
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

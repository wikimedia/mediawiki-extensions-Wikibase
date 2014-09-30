/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
( function( wb, $ ) {
	'use strict';

	var MODULE = wb.store;

	/**
	 * Entity store managing wikibase.datamodel.Entity objects.
	 *
	 * Subclasses have to implement at least one of get, getMultiple or getMultipleRaw.
	 *
	 * @constructor
	 * @abstract
	 * @since 0.5
	 */
	var SELF = MODULE.EntityStore = function WbEntityStore() {};

	$.extend( SELF.prototype, {
		/**
		 * Returns a promise resolving to the entity, undefined or null.
		 * @since 0.5
		 *
		 * @param {string} entityId
		 * @return {jQuery.Promise}
		 *         Resolved parameters:
		 *         - {wikibase.store.FetchedContent|undefined|null}
		 *         No rejected parameters.
		 */
		get: function( entityId ) {
			var deferred = $.Deferred();
			var self = this;

			this.getMultiple( [ entityId ] )
			.done( function( entities ) {
				deferred.resolve( entities[ 0 ] );
			} )
			// FIXME: Evaluate failing promise
			.fail( deferred.reject );

			return deferred.promise();
		},

		/**
		 * Returns a promise resolving to an array with elements entity, undefined or null.
		 * @since 0.5
		 *
		 * @param {string[]} entityIds
		 * @return {jQuery.Promise}
		 *         Resolved parameters:
		 *         - {wikibase.store.FetchedContent|undefined|null[]}
		 *         No rejected parameters.
		 */
		getMultiple: function( entityIds ) {
			var deferred = $.Deferred();

			$.when.apply( $, this.getMultipleRaw( entityIds ) )
			.done( function( /*…*/ ) {
				deferred.resolve( $.makeArray( arguments ) );
			} )
			// FIXME: Evaluate failing promise
			.fail( function() {
				deferred.reject();
			} );

			return deferred.promise();
		},

		/**
		 * Returns an array of promises resolving to entity, undefined or null.
		 * @since 0.5
		 *
		 * @param {string[]} entityIds
		 * @return {jQuery.Promise[]}
		 *         Resolved parameters:
		 *         - {wikibase.store.FetchedContent|undefined|null}
		 *         No rejected parameters.
		 */
		getMultipleRaw: function( entityIds ) {
			return $.map( entityIds, $.proxy( this.get, this ) );
		}
	} );

}( wikibase, jQuery ) );

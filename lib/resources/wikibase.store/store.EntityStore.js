/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
( function( wb, $ ) {
	'use strict';

	var MODULE = wb.store;

	/**
	 * Entity store managing wikibase.datamodel.Entity objects.
	 * @constructor
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
		get: util.abstractMember
	} );

}( wikibase, jQuery ) );

/**
 * @file
 * @ingroup Wikibase
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( mw, wb, $, undefined ) {
'use strict';

/**
 * Interface definition for generic entity store.
 * @constructor
 * @abstract
 * @since 0.2
 *
 * @todo which parameters will be available in the promises callbacks?
 */
wb.EntityStore = function( baseRevId ) {};

wb.EntityStore.prototype = {
	/**
	 * Stores a single claim for one entity. Returns a promise which will be resolved/rejected
	 * depending on the operations success.
	 *
	 * @param {String} entityId
	 * @param {wb.Claim} claim
	 * @returns jQuery.Promise
	 */
	storeClaim: wb.utilities.abstractFunction,

	/**
	 * Loads an entities claims. Returns a promise which will be resolved/rejected depending on
	 * the operations success.
	 *
	 * @param {String} entityId
	 * @returns jQuery.Promise
	 */
	getClaims: wb.utilities.abstractFunction,

	/**
	 * Stores a single statement for one entity. Returns a promise which will be resolved/rejected
	 * depending on the operations success.
	 *
	 * @param {String} entityId
	 * @param {wb.Statement} statement
	 * @returns jQuery.Promise
	 */
	storeStatement: wb.utilities.abstractFunction,

	/**
	 * Fetches an entities statements. Returns a promise which will be resolved/rejected depending
	 * on the operations success.
	 *
	 * @param {String} entityId
	 * @returns jQuery.Promise
	 */
	getStatements: wb.utilities.abstractFunction,

	/**
	 * Stores a single qualifier (Auxiliary Snak) for an existing claim. Returns a promise which
	 * will be resolved/rejected depending on the operations success.
	 *
	 * @param {String} guid for identifying the claim a qualifier should be stored for
	 * @param {wb.Snak} qualifier
	 * @returns jQuery.Promise
	 */
	storeClaimQualifier: wb.utilities.abstractFunction,

	/**
	 * Stores a single source reference for an existing statement. Returns a promise which will be
	 * resolved/rejected depending on the operations success.
	 *
	 * @param {String} GUID for identifying the claim
	 * @param {wb.Snak} qualifier
	 * @returns jQuery.Promise
	 */
	storeStatementReference: wb.utilities.abstractFunction,

	/**
	 * Stores an entity. Returns a promise which will be resolved when the entity has been stored
	 * successfully. The promise will be rejected if some error occurred while storing.
	 *
	 * @param {wb.Entity} entity
	 * @returns jQuery.Promise
	 */
	storeEntity: wb.utilities.abstractFunction,

	/**
	 * Loads one or more entities from the store. A promise will be returned. When resolved the
	 * entities will be available in registered callbacks.
	 *
	 * @param {String|String[]|wb.SiteLink} criteria
	 * @returns jQuery.Promise
	 */
	getEntities: function( criteria ) {
		var accessFn,
			sample = $.isArray( criteria ) ? criteria[0] : criteria;

		if( typeof sample === 'string' ) {
			accessFn = this.getEntitiesByIds;
		}
		else if( sample instanceof wb.SiteLink ) {
			accessFn = this.getEntityBySiteLink;
		}

		return accessFn( criteria );
	},

	/**
	 * Loads one or more entities with the given IDs from the store.
	 *
	 * @param {String|String[]} IDs
	 * @returns jQuery.Promise
	 */
	getEntitiesByIds: wb.utilities.abstractFunction,

	/**
	 * Loads one or more entities which link to the given site from the store.
	 *
	 * @todo this is something Item specific!
	 *
	 * @param {Title|Title[]} IDs
	 * @returns jQuery.Promise
	 */
	getEntityBySiteLink: wb.utilities.abstractFunction
};

}( mediaWiki, wikibase, jQuery ) );

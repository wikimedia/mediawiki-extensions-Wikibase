/**
 * @file
 * @ingroup WikibaseLib
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( wb, dv, $, undefined ) {
'use strict';

/**
 * Interface definition for generic entity store.
 * @constructor
 * @abstract
 * @since 0.2
 *
 * @todo which parameters will be available in the promises callbacks?
 * @todo probably the cleanest solution if there will be deriving constructors for all kinds of
 *       entities. ItemStore, PropertyStore etc... wb.repoApi could still be an implementation for
 *       more than just one of these.
 */
wb.EntityStore = function() {};

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
	 * the operation's success.
	 *
	 * @param {String} entityId
	 * @returns jQuery.Promise
	 */
	getClaims: wb.utilities.abstractFunction,

	/**
	 * Stores a single qualifier (Auxiliary Snak) for an existing claim. Returns a promise which
	 * will be resolved/rejected depending on the operation's success.
	 *
	 * @param {String} GUID for identifying the claim
	 * @param {wb.Snak} qualifier
	 * @returns jQuery.Promise
	 */
	setClaimQualifier: wb.utilities.abstractFunction,

	/**
	 * Removes a single qualifier (Auxiliary Snak) of a claim. Returns a promise which will be
	 * resolved/rejected depending on the operation's success.
	 *
	 * @param {String} GUID for identifying the claim
	 * @param {wb.Snak} qualifier Has to exactly match the qualifier which should be removed.
	 * @returns jQuery.Promise
	 */
	removeClaimQualifier: wb.utilities.abstractFunction,

	/**
	 * @param {String} GUID for identifying the claim
	 * @param {dv.DataValue}
	 * @returns jQuery.Promise
	 */
	setClaimValue: wb.utilities.abstractFunction,

	/**
	 * This will add or overwrite a single source reference for an existing statement. Returns a
	 * promise which will be resolved/rejected depending on the operation's success.
	 *
	 * @param {String} GUID for identifying the statement
	 * @param {wb.Snak[]} reference A set of Snaks which represent describe the reference
	 * @param {String} [refHash] If set, the given reference will overwrite this one
	 * @returns jQuery.Promise
	 */
	setStatementReference: wb.utilities.abstractFunction,

	/**
	 * Removes a single source reference for an existing statement. Returns a promise which will be
	 * resolved/rejected depending on the operation's success.
	 *
	 * @param {String} GUID for identifying the statement
	 * @param {String} refHash for identifying the reference
	 * @returns jQuery.Promise
	 */
	removeStatementReference: wb.utilities.abstractFunction,

	/**
	 * Sets the rank for a statement, overwrites the current rank.
	 *
	 * @param {String} GUID for identifying the statement
	 * @param {Number} rank The new rank.
	 * @returns jQuery.Promise
	 */
	setStatementRank: wb.utilities.abstractFunction,

	/**
	 * This will add or replace an entity. Returns a promise which will be resolved when the entity
	 * has been stored successfully. The promise will be rejected if some error occurred while
	 * storing the entity.
	 *
	 * @param {wb.Entity} entity
	 * @param {String} [id] for overwriting an existing entity
	 * @returns jQuery.Promise
	 */
	setEntity: wb.utilities.abstractFunction,

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

}( wikibase, dataValues, jQuery ) );

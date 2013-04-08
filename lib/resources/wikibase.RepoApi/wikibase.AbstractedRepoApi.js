/**
 * @file
 * @ingroup WikibaseLib
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author Tobias Gritschacher
 * @author H. Snater < mediawiki@snater.com >
 * @author Marius Hoch < hoo@online.de >
 */
( function( mw, wb, $ ) {
'use strict';

/**
 * Provides abstraced access functions for the wikibase Repo Api
 *
 * @constructor
 * @since 0.4
 */
wb.AbstractedRepoApi = function wbAbstractedRepoApi() {};

$.extend( wb.AbstractedRepoApi.prototype, {

	/**
	 * wikibase.RepoApi object for internal usage. By having this initialized in the prototype, we can
	 * share one instance for all instances of the wikibase API.
	 * @type wb.RepoApi
	 */
	_repoApi: new wb.RepoApi(),

	/**
	 * Removes an existing claim.
	 *
	 * @param {String} claimGuid The GUID of the Claim to be removed (wb.Claim.getGuid)
	 * @param {Number} baseRevId
	 * @return {jQuery.Promise} When resolved, the first parameter in callbacks is the related
	 *         page info which holds the revision ID of the related entity.
	 */
	removeClaim: function( claimGuid, baseRevId ) {
		var deferred = $.Deferred();

		this._repoApi.removeClaim( claimGuid, baseRevId )
		.done( function( result ) {
			deferred.resolve( result.pageinfo );
		} ).fail( function() {
			deferred.reject.apply( deferred, arguments );
		} );

		return deferred.promise();
	},

	/**
	 * Will remove one or more existing References of a Statement.
	 *
	 * @since 0.4
	 *
	 * @param {string} statementGuid
	 * @param {string|string[]} referenceHashes One or more hashes of the References to be removed.
	 * @param {number} baseRevId
	 * @return {jQuery.Promise} Done callbacks will receive new base revision ID as first parameter.
	 */
	removeReferences: function( statementGuid, referenceHashes, baseRevId ) {
		return this._abstract(
			this._repoApi.removeReferences( statementGuid, referenceHashes, baseRevId ),
			function( result ) {
				return [ result.pageinfo ];
			}
		);
	},

	/**
	 * Will set a new Reference for a Statement.
	 *
	 * @since 0.4
	 *
	 * @param {string} statementGuid
	 * @param {wb.SnakList} snaks
	 * @param {number} baseRevId
	 * @param {string} [referenceHash] A hash of the reference that should be updated.
	 *        If not provided, a new reference is created.
	 * @return {jQuery.Promise} If resolved, this will get a wb.Reference object as first parameter
	 *         and the last base revision as second parameter.
	 */
	setReference: function( statementGuid, snaks, baseRevId, referenceHash ) {
		var snakJson = snaks.toJSON();
		return this._abstract(
			this._repoApi.setReference( statementGuid, snakJson, baseRevId, referenceHash ),
			function( result ) {
				return [
					wb.Reference.newFromJSON( result.reference ),
					result.pageinfo
				];
			}
		);
	},

	/**
	 * Gets one or more Entities.
	 *
	 * @param {String[]|String} ids
	 * @param {String[]|String} [props] Key(s) of property/ies to retrieve from the API
	 *                          default: null (will return all properties)
	 * @param {String[]}        [languages]
	 *                          default: null (will return results in all languages)
	 * @param {String[]|String} [sort] Key(s) of property/ies to sort on
	 *                          default: null (unsorted)
	 * @param {String}          [dir] Sort direction may be 'ascending' or 'descending'
	 *                          default: null (ascending)
	 * @return {jQuery.Promise} If successful, the first parameter of the done callbacks will be
	 *         an object with keys of the entity's IDs and values of the requested entities
	 *         represented as wb.Entity objects. If a requested Entity does not exist, it will not
	 *         be represented in the result.
	 *
	 * @todo Requires more? tests!
	 */
	getEntities: function( ids, props, languages, sort, dir ) {
		return this._abstract(
			this._repoApi.getEntities( ids, props, languages, sort, dir ),
			function( result ) {
				var entities = {},
					unserializer = ( new wb.serialization.SerializerFactory() ).newUnserializerFor( wb.Entity );

				$.each( result.entities, function( id, entityData ) {
					if( !entityData.id ) {
						return; // missing entity
					}

					var entity = unserializer.unserialize( entityData );
					entities[ entity.getId() ] = entity;
				} );

				return [ entities ];
			}
		);
	},

	/**
	 * Creates/Updates an entire claim.
	 *
	 * @param {wb.Claim|wb.Statement} claim
	 * @param {Number} baseRevId
	 * @return {jQuery.Promise}
	 */
	setClaim: function( claim, baseRevId ) {
		var claimJson = claim.toJSON();
		return this._abstract(
			this._repoApi.setClaim( claimJson, baseRevId ),
			function( result ) {
				return [
					wb.Claim.newFromJSON( result.claim ),
					result.pageinfo
				];
			}
		);
	},

	/**
	 * Creates a claim.
	 * @todo Needs testing. It would be necessary to create a property for creating a claim.
	 *       The API does not support setting a data type for an entity at the moment.
	 *
	 * @param {String} entityId Entity id
	 * @param {Number} baseRevId revision id
	 * @param {wb.Snak} mainSnak The new Claim's Main Snak.
	 * @return {jQuery.Promise} When resolved, the first parameter in callbacks is the saved
	 *         wb.Claim object which holds its final GUID.
	 */
	createClaim: function( entityId, baseRevId, mainSnak ) {
		var params = this._claimApiParams( mainSnak );
		return this._abstract(
			this._repoApi.createClaim(
				entityId, baseRevId,  params.snaktype, params.property, params.value
			),
			this._claimApiCallback
		);
	},

	/**
	 * Changes the Main Snak of an existing claim.
	 * @todo Needs testing just like createClaim()!
	 *
	 * @param {String} claimGuid The GUID of the Claim to be changed (wb.Claim.getGuid)
	 * @param {Number} baseRevId
	 * @param {wb.Snak} mainSnak The new value to be set as the claims Main Snak.
	 * @return {jQuery.Promise} When resolved, the first parameter in callbacks is the changed
	 *         wb.Claim object with the updated Main Snak.
	 */
	setClaimValue: function( claimGuid, baseRevId, mainSnak ) {
		var params = this._claimApiParams( mainSnak );
		return this._abstract(
			this._repoApi.setClaimValue(
				claimGuid, baseRevId, params.snaktype, params.property, params.value
			),
			this._claimApiCallback
		);
	},

	/**
	 * Helper function for createClaim and setClaimValue. Both have very similar parameters.
	 *
	 * @since 0.4
	 *
	 * @param {wb.Snak} mainSnak
	 * @return {object}
	 *
	 * @throws {Error} If no Snak instance is given as second parameter
	 */
	_claimApiParams: function( mainSnak ) {
		if( !mainSnak instanceof wb.Snak ) {
			throw new Error( 'A wikibase.Snak object is required as Main Snak' );
		}
		var snakJson = mainSnak.toJSON(),
			params = {
				snaktype: mainSnak.getType(),
				// NOTE: currently 'wbsetclaimvalue' API allows to change snak type but not property,
				//  set it anyhow. Returned promise won't propagate the API warning we will get here.
				property: snakJson.property
			};

		if( snakJson.datavalue !== undefined ) {
			params.value = snakJson.datavalue.value;
		} else {
			params.value = null;
		}

		return params;
	},

	/**
	 * Handles the results of claim api calls
	 *
	 * @since 0.4
	 *
	 * @param {object} result
	 * @return {object}
	 */
	_claimApiCallback: function( result ) {
		return [
			wb.Claim.newFromJSON( result.claim ),
			result.pageinfo
		];
	},

	/**
	 * This will do certain things to the given data and return a $.promise
	 *
	 * @since 0.4
	 *
	 * @param {jQuery.Promise} apiPromise
	 * @param {Function} callbackForAbstraction Called when the request is resolved, will get all
	 *        parameters the original resolved post promise gets. Can return an array whose members
	 *        will then serve as parameters for the public promise.
	 * @return {jQuery.Promise}
	 */
	_abstract: function( apiPromise, callbackForAbstraction ) {
		var deferred = $.Deferred(),
			self = this;

		apiPromise
		.done( function() {
			var args = callbackForAbstraction.apply( self, arguments );
			deferred.resolve.apply( deferred, args );
		} )
		.fail( deferred.reject );

		return deferred.promise();
	}
} );

}( mediaWiki, wikibase, jQuery ) );

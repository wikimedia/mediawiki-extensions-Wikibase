/**
 * @file
 * @ingroup WikibaseLib
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author Tobias Gritschacher
 * @author H. Snater < mediawiki@snater.com >
 * @author Marius Hoch < hoo@online.de >
 */
( function( mw, wb, $, undefined ) {
'use strict';

var PARENT = wb.RepoApi;
/**
 * Extends wb.RepoApi with some more abstracted features which require quite heavy dependencies
 * This is probably what you want to use as dependency when on the repo!
 * @constructor
 * @since 0.4
 */

wb.RepoApi = wb.utilities.inherit( 'WbRepoApi', PARENT, {
	/**
	 * Will remove one or more existing References of a Statement.
	 *
	 * @since 0.4
	 *
	 * @param {string} statementGuid
	 * @param {string|string[]} referenceHashes One or more hashes of the References to be removed.
	 * @param {number} baseRevId
	 * @return jQuery.Promise Done callbacks will receive new base revision ID as first parameter.
	 */
	removeReferences: function( statementGuid, referenceHashes, baseRevId ) {
		var params = {
			action: 'wbremovereferences',
			statement: statementGuid,
			references: this._normalizeParam( referenceHashes ),
			baserevid: baseRevId
		};

		return this._postAndPromiseWithAbstraction( params, function( result ) {
			return [ result.pageinfo ];
		} );
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
	 * @return jQuery.Promise If resolved, this will get a wb.Reference object as first parameter
	 *         and the last base revision as second parameter.
	 */
	setReference: function( statementGuid, snaks, baseRevId, referenceHash ) {
		var params = {
			action: 'wbsetreference',
			statement: statementGuid,
			snaks: $.toJSON( snaks.toJSON() ),
			baserevid: baseRevId
		};

		if( referenceHash ) {
			params.reference = referenceHash;
		}

		return this._postAndPromiseWithAbstraction( params, function( result ) {
			return [
				wb.Reference.newFromJSON( result.reference ),
				result.pageinfo
			];
		} );
	},

	/**
	 * Same as post() or get() but will return a jQuery.Promise which will resolve with some
	 * different arguments, specified in a callback.
	 *
	 * @since 0.4
	 *
	 * @param {string} method Either 'post' or 'get'
	 * @param params
	 * @param {Function} callbackForAbstraction Called when post request is resolved, will get all
	 *        parameters the original resolved post promise gets. Can return an array whose members
	 *        will then serve as parameters for the public promise.
	 * @return jQuery.Promise
	 */
	_requestAndPromiseWithAbstraction: function( method, params, callbackForAbstraction ) {
		var deferred = $.Deferred(),
			self = this;

		this[ method ]( params )
		.done( function() {
			var args = arguments;
			// For sanity: Make sure we got the whole wikibase.store around
			mw.loader.using( 'wikibase.store', function() {
				args = callbackForAbstraction.apply( self, args );
				deferred.resolve.apply( deferred, args );
			} );
		} )
		.fail( deferred.reject );

		return deferred.promise();
	},

	/**
	 * Helper function for 'wbcreateclaim' and 'wbsetclaimvalue'. Both have very similar handling
	 * and both will return a $.Promise which returns information about the changed/created claim
	 * and the pageinfo in its callback.
	 *
	 * @param {Number} baseRevId
	 * @param {wb.Snak} mainSnak
	 * @param {Object} params 'action' and 'entity' or 'claim' parameter information
	 * @return {jQuery.Promise}
	 */
	_claimApiCall: function( baseRevId, mainSnak, params ) {
		if( !( mainSnak instanceof wb.Snak ) ) {
			throw new Error( 'A wikibase.Snak object is required as Main Snak' );
		}
		var snakJson = mainSnak.toJSON();

		$.extend( params, {
			baserevid: baseRevId,
			snaktype: mainSnak.getType(),
			// NOTE: currently 'wbsetclaimvalue' API allows to change snak type but not property,
			//  set it anyhow. Returned promise won't propagate the API warning we will get here.
			property: snakJson.property
		} );

		if( snakJson.datavalue !== undefined ) {
			params.value = $.toJSON( snakJson.datavalue.value );
		}

		return this._postAndPromiseWithAbstraction( params, function( result ) {
			return [
				wb.Claim.newFromJSON( result.claim ),
				result.pageinfo
			];
		} );
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
	 * @todo Requires tests!
	 */
	getEntities: function( ids, props, languages, sort, dir ) {
		var params = {
			action: 'wbgetentities',
			ids: ids,
			props: this._normalizeParam( props ),
			languages: this._normalizeParam( languages ),
			sort: this._normalizeParam( sort ),
			dir: dir || undefined
		};

		return this._getAndPromiseWithAbstraction( params, function( result ) {
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
		} );
	},

	/**
	 * Creates/Updates an entire claim.
	 *
	 * @param {wb.Claim|wb.Statement} claim
	 * @param {Number} baseRevId
	 * @return {jQuery.Promise}
	 */
	setClaim: function( claim, baseRevId ) {
		var params = {
			action: 'wbsetclaim',
			claim:$.toJSON( claim.toJSON() ),
			baserevid: baseRevId
		};

		return this._postAndPromiseWithAbstraction( params, function( result ) {
			return [
				wb.Claim.newFromJSON( result.claim ),
				result.pageinfo
			];
		} );
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
	 *
	 * @throws {Error} If no Snak instance is given in the third parameter
	 */
	createClaim: function( entityId, baseRevId, mainSnak ) {
		return this._claimApiCall( baseRevId, mainSnak, {
			action: 'wbcreateclaim',
			entity: entityId
		} );
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
	 *
	 * @throws {Error} If no Snak instance is given in the third parameter
	 */
	setClaimValue: function( claimGuid, baseRevId, mainSnak ) {
		return this._claimApiCall( baseRevId, mainSnak, {
			action: 'wbsetclaimvalue',
			claim: claimGuid
		} );
	},

	/**
	 * @see _requestAndPromiseWithAbstraction
	 */
	_postAndPromiseWithAbstraction: function( params, callbackForAbstraction ) {
		return this._requestAndPromiseWithAbstraction( 'post', params, callbackForAbstraction );
	},

	/**
	 * @see _requestAndPromiseWithAbstraction
	 */
	_getAndPromiseWithAbstraction: function( params, callbackForAbstraction ) {
		return this._requestAndPromiseWithAbstraction( 'get', params, callbackForAbstraction );
	}
} );

}( mediaWiki, wikibase, jQuery ) );

/**
 * @file
 * @ingroup WikibaseLib
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author Tobias Gritschacher
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $, undefined ) {
'use strict';

var PARENT = wb.EntityStore;

/**
 * Constructor to create an API object for interaction with the Wikibase API.
 * This implements the wikibase.EntityStore, so this represents the server sided store which will
 * be accessed via the API.
 * @constructor
 * @extends wb.EntityStore
 * @since 0.3 (available before but not really working in some parts)
 */
wb.Api = wb.utilities.inherit( PARENT, {

	/**
	 * mediaWiki.Api object for internal usage. By having this initialized in the prototype, we can
	 * share one instance for all instances of the wikibase API.
	 * @type mw.Api
	 */
	_api: new mw.Api(),

	/**
	 * Creates a new entity with given data.
	 *
	 * @param {Object} [data] The entity data (may be omitted to create an empty entity)
	 * @return {jQuery.Promise}
	 */
	createEntity: function( data ) {
		var params = {
			action: 'wbeditentity',
			data: $.toJSON( data )
		};
		return this.repoPost( params );
	},

	/**
	 * Edits an entity.
	 *
	 * @param {String} id Entity id
	 * @param {Number} baseRevId Revision id the edit shall be performed on
	 * @param {Object} data The entity's structure
	 * @param {Boolean} [clear] Whether to clear whole entity before editing (default: false)
	 * @return {jQuery.Promise}
	 */
	editEntity: function( id, baseRevId, data, clear ) {
		var params = {
			action: 'wbeditentity',
			id: id,
			baserevid: baseRevId,
			data: $.toJSON( data )
		};

		if ( clear ) {
			params.clear = true;
		}

		return this.repoPost( params );
	},

	/**
	 * Gets one or more entities.
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
	 * @return {jQuery.Promise}
	 */
	getEntities: function( ids, props, languages, sort, dir ) {
		// get a string from array or string for certain parameters
		var normalizeParam = function( value ) {
			return $.isArray( value )
				? value.join( '|' )
				: ( value || undefined );
		};

		var params = {
			action: 'wbgetentities',
			ids: ids,
			props: normalizeParam( props ),
			languages: normalizeParam( languages ),
			sort: normalizeParam( sort ),
			dir: dir || undefined
		};

		return this.repoGet( params );
	},

	/**
	 * Sets the label of an entity via the API.
	 *
	 * @param {String} id entity id
	 * @param {Number} baseRevId revision id
	 * @param {String} label the label to set
	 * @param {String} language the language in which the label should be set
	 * @return {jQuery.Promise}
	 */
	setLabel: function( id, baseRevId, label, language ) {
		var params = {
			action: "wbsetlabel",
			id: id,
			value: label,
			language: language,
			baserevid: baseRevId
		};
		return this.repoPost( params );
	},

	/**
	 * Sets the description of an entity via the API.
	 *
	 * @param {String} id entity id
	 * @param {Number} baseRevId revision id
	 * @param {String} description the description to set
	 * @param {String} language the language in which the description should be set
	 * @return {jQuery.Promise}
	 */
	setDescription: function( id, baseRevId, description, language ) {
		var params = {
			action: "wbsetdescription",
			id: id,
			value: description,
			language: language,
			baserevid: baseRevId
		};
		return this.repoPost( params );
	},

	/**
	 * Adds and/or remove a number of aliases of an item via the API.
	 *
	 * @param {String} id entity id
	 * @param {Number} baseRevId revision id
	 * @param {String[]|String} add Alias(es) to add
	 * @param {String[]|String} remove Alias(es) to remove
	 * @param {String} language the language in which the aliases should be added/removed
	 * @return {jQuery.Promise}
	 */
	setAliases: function( id, baseRevId, add, remove, language ) {
		if ( $.isArray( add ) ) {
			add = add.join( '|' );
		}
		if ( $.isArray( remove ) ) {
			remove = remove.join( '|' );
		}
		var params = {
			action: "wbsetaliases",
			id: id,
			add: add,
			remove: remove,
			language: language,
			baserevid: baseRevId
		};
		return this.repoPost( params );
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
	 * Removes an existing claim.
	 *
	 * @param {String} claimGuid The GUID of the Claim to be removed (wb.Claim.getGuid)
	 * @param {Number} baseRevId
	 * @return {jQuery.Promise} When resolved, the first parameter in callbacks is the related
	 *         page info which holds the revision ID of the related entity.
	 */
	removeClaim: function( claimGuid, baseRevId ) {
		var deferred = $.Deferred();

		this.repoPost( {
			action: 'wbremoveclaims',
			claim: claimGuid
		} ).done( function( result ) {
			deferred.resolve( result.pageinfo );
		} ).fail( function() {
			deferred.reject.apply( deferred, arguments );
		} );

		return deferred.promise();
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
		var snakJson = mainSnak.toJSON(),
			deferred = $.Deferred();

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

		this.repoPost( params )
		.done( function( result ) {
			// return changed wb.Claim object in the callback
			var claim = wb.Claim.newFromJSON( result.claim );
			deferred.resolve( claim, result.pageinfo );
		} )
		.fail( function() {
			deferred.reject.apply( deferred, arguments );
		} );

		return deferred.promise();
	},

	/**
	 * Sets a site link for an item via the API.
	 *
	 * @param {String} id entity id
	 * @param {Number} baseRevId revision id
	 * @param {String} site the site of the link
	 * @param {String} title the title to link to
	 * @return {jQuery.Promise}
	 */
	setSitelink: function( id, baseRevId, site, title ) {
		var params = {
			action: "wbsetsitelink",
			id: id,
			linksite: site,
			linktitle: title,
			baserevid: baseRevId
		};
		return this.repoPost( params );
	},

	/**
	 * Removes a sitelink of an item via the API.
	 *
	 * @param {String} id entity id
	 * @param {Number} baseRevId revision id
	 * @param {String} site the site of the link
	 * @return {jQuery.Promise}
	 */
	removeSitelink: function( id, baseRevId, site ) {
		return this.setSitelink( id, baseRevId, site, '' );
	},

	/**
	 * This function populates wbRepoEditToken. After this function ran
	 * edits on the repo can be made safely.
	 *
	 * @return {jQuery.Promise}
	 */
	populateWbRepoEditToken: function() {
		if ( mw.config.exists( 'wbRepoEditToken' ) ) {
			return $( true ).promise();
		}
		return this.repoGet( {
			action: 'query',
			intoken: 'edit',
			titles: 'Main page',
			prop: 'info',
			indexpageids: 1
		} )
		.done( function( data ) {
			mw.config.set( 'wbRepoEditToken', data.query.pages[ data.query.pageids[0] ].edittoken );
		} );
	},

	/**
	 * Set the required options and parameters for a repo call from a client, if needed
	 *
	 * @param {Object} params parameters for the API call
	 * @param {Object} ajax options
	 */
	_extendRepoCallParams: function( params, options ) {
		var localServerRaw = mw.config.get( 'wgServer' ).replace( /.*\/\//, '' ),
			repoServerRaw = mw.config.get( 'wbRepoUrl' ).replace( /.*\/\//, '' ).replace( /\/.*/, '' );

		options.url = mw.config.get( 'wbRepoUrl' ) + mw.config.get( 'wbRepoScriptPath' ) + '/api.php';

		if ( localServerRaw === repoServerRaw ) {
			// We don't need/ want CORS when on the same domain
			return;
		}

		var currentServer = mw.config.get( 'wgServer' );
		if ( currentServer.indexOf( '//' ) === 0 ) {
			// The origin parameter musn't be protocol relative
			currentServer = document.location.protocol + currentServer;
		}

		params.origin = currentServer;
	},

	/**
	 * Submits the AJAX request to the API of the repo and triggers on the response. This will
	 * automatically add the required 'token' information for editing into the given parameters
	 * sent to the API.
	 * If this should be used on a client wiki this.populateWbRepoEditToken needs to be run first!
	 * @see mw.Api.post
	 *
	 * @param {Object} params parameters for the API call
	 * @return {jQuery.Promise}
	 *
	 * @throws {Error} If a parameter is not specified properly
	 */
	repoPost: function( params ) {
		var options = {};
		this._extendRepoCallParams( params, options );

		$.each( params, function( key, value ) {
			if ( value === undefined || value === null ) {
				throw new Error( 'Parameter "' + key + '" is not specified properly.' );
			}
		} );

		var token = mw.config.exists( 'wbRepoEditToken' ) ? mw.config.get( 'wbRepoEditToken' ) : mw.user.tokens.get( 'editToken' );

		$.extend( params, { token: token } );

		return this._api.post( params, options );
	},

	/**
	 * Performs an API get request.
	 * @see mw.Api.get
	 * @since 0.3
	 *
	 * @param {Array} params
	 */
	repoGet: function( params ) {
		var options = {};
		this._extendRepoCallParams( params, options );

		return this._api.get( params, options );
	}

} );

	// TODO: step by step implementation of the store, starting with basic claim stuff

}( mediaWiki, wikibase, jQuery ) );

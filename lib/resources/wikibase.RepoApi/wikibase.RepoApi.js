/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author Tobias Gritschacher
 * @author H. Snater < mediawiki@snater.com >
 * @author Marius Hoch < hoo@online.de >
 */
( function( mw, wb, $ ) {
'use strict';

var repoConfig = mw.config.get( 'wbRepo' );
var repoApiEndpoint = repoConfig.url + repoConfig.scriptPath + '/api.php';
var mwApi = wb.api.getLocationAgnosticMwApi( repoApiEndpoint );

/**
 * Constructor to create an API object for interaction with the repo Wikibase API.
 * @constructor
 * @since 0.4 (since 0.3 as wb.Api without support for client usage)
 */
wb.RepoApi = function wbRepoApi() {
};

$.extend( wb.RepoApi.prototype, {
	/**
	 * mediaWiki.Api object for internal usage. By having this initialized in the prototype, we can
	 * share one instance for all instances of the wikibase API.
	 * @type mw.Api
	 */
	_api: mwApi,

	/**
	 * Creates a new entity with the given type and data.
	 *
	 * @param {String} type The type of the entity that should be created.
	 * @param {Object} [data] The entity data (may be omitted to create an empty entity)
	 * @return {jQuery.Promise}
	 */
	createEntity: function( type, data ) {
		var params = {
			action: 'wbeditentity',
			data: JSON.stringify( data ),
			'new': type
		};
		return this.post( params );
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
			data: JSON.stringify( data )
		};

		if ( clear ) {
			params.clear = true;
		}

		return this.post( params );
	},

	/**
	 * Formats values.
	 *
	 * @param {Object} dataValue
	 * @param {Object} [options]
	 * @param {string} [dataType]
	 * @param {string} [outputFormat]
	 * @return {jQuery.Promise}
	 */
	formatValue: function( dataValue, options, dataType, outputFormat ) {
		var params = {
			action: 'wbformatvalue',
			datavalue:  JSON.stringify( dataValue ),
			options: JSON.stringify( options || {} )
		};

		if( dataType ) {
			params.datatype = dataType;
		}

		if( outputFormat ) {
			params.generate = outputFormat;
		}

		return this._api.get( params );
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
	 * @return {jQuery.Promise}
	 */
	getEntities: function( ids, props, languages, sort, dir ) {
		var params = {
			action: 'wbgetentities',
			ids: this._normalizeParam( ids ),
			props: this._normalizeParam( props ),
			languages: this._normalizeParam( languages ),
			sort: this._normalizeParam( sort ),
			dir: dir || undefined
		};

		return this._api.get( params );
	},

	/**
	 * Gets an Entity which is linked with a given page.
	 *
	 * @param {String[]|String} [sites] Site(s) the given page is linked on
	 * @param {String[]|String} [titles] Linked page(s)
	 * @param {String[]|String} [props] Key(s) of property/ies to retrieve from the API
	 *                          default: null (will return all properties)
	 * @param {String[]}        [languages]
	 *                          default: null (will return results in all languages)
	 * @param {String[]|String} [sort] Key(s) of property/ies to sort on
	 *                          default: null (unsorted)
	 * @param {String}          [dir] Sort direction may be 'ascending' or 'descending'
	 *                          default: null (ascending)
	 * @param {bool}            [normalize] Whether to normalize titles server side
	 * @return {jQuery.Promise}
	 */
	getEntitiesByPage: function( sites, titles, props, languages, sort, dir, normalize ) {
		var params = {
			action: 'wbgetentities',
			sites: this._normalizeParam( sites ),
			titles: this._normalizeParam( titles ),
			props: this._normalizeParam( props ),
			languages: this._normalizeParam( languages ),
			sort: this._normalizeParam( sort ),
			dir: dir || undefined,
			normalize: normalize || undefined
		};

		return this._api.get( params );
	},

	/**
	 * Parses values.
	 *
	 * @param {string} parser
	 * @param {string[]} values
	 * @param {Object} options
	 * @return {jQuery.Promise}
	 */
	parseValue: function( parser, values, options ) {
		var params = {
			action: 'wbparsevalue',
			parser: parser,
			values: values.join( '|' ),
			options: JSON.stringify( options )
		};
		return this._api.get( params );
	},

	/**
	 * Searches for entities containing the given text.
	 *
	 * @param {String} search search for this text
	 * @param {String} language search in this language
	 * @param {String} type search for this entity type
	 * @param {Number} limit maximum number of results to return
	 * @param {Number} offset offset where to continue the search
	 * @return {jQuery.Promise}
	 */
	searchEntities: function( search, language, type, limit, offset ) {
		var params = {
			action: 'wbsearchentities',
			search: search,
			language: language,
			type: type,
			limit: limit || undefined,
			'continue': offset || undefined
		};

		return this._api.get( params );
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
			action: 'wbsetlabel',
			id: id,
			value: label,
			language: language,
			baserevid: baseRevId
		};
		return this.post( params );
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
			action: 'wbsetdescription',
			id: id,
			value: description,
			language: language,
			baserevid: baseRevId
		};
		return this.post( params );
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
		add = this._normalizeParam( add );
		remove = this._normalizeParam( remove );
		var params = {
			action: 'wbsetaliases',
			id: id,
			add: add,
			remove: remove,
			language: language,
			baserevid: baseRevId
		};
		return this.post( params );
	},

	/**
	 * Creates/Updates an entire claim.
	 *
	 * @param {object} claim
	 * @param {number} baseRevId
	 * @param {number} [index] The claim index. Only needs to be specified if the claim's index
	 *        within the list of all claims of the parent entity shall be changed.
	 * @return {jQuery.Promise}
	 */
	setClaim: function( claim, baseRevId, index ) {
		var params = {
			action: 'wbsetclaim',
			claim: JSON.stringify( claim ),
			baserevid: baseRevId
		};

		if( index !== undefined ) {
			params.index = index;
		}

		return this.post( params );
	},

	/**
	 * Creates a claim.
	 *
	 * @param {string} entityId Entity id
	 * @param {number} baseRevId revision id
	 * @param {string} snakType The type of the snak
	 * @param {string} property Id of the snak's property
	 * @param {Object|string} value The value to set the datavalue of the claim's main snak to
	 * @return {jQuery.Promise}
	 */
	createClaim: function( entityId, baseRevId, snakType, property, value ) {
		var params = {
			action: 'wbcreateclaim',
			entity: entityId,
			baserevid: baseRevId,
			snaktype: snakType,
			property: property
		};

		if ( value ) {
			params.value = JSON.stringify( value );
		}

		return this.post( params );
	},

	/**
	 * Removes an existing claim.
	 *
	 * @param {String} claimGuid The GUID of the Claim to be removed (wb.datamodel.Claim.getGuid)
	 * @return {jQuery.Promise}
	 */
	removeClaim: function( claimGuid ) {
		return this.post( {
			action: 'wbremoveclaims',
			claim: claimGuid
		} );
	},

	/**
	 * Returns claims of a specific entity by providing an entity id or a specific claim by
	 * providing a claim GUID.
	 *
	 * @param {string} entityId Entity id
	 * @param {string} [propertyId] Only return claims featuring this property
	 * @param {string} claimGuid GUID of the claim to return. Either claimGuid or entityID has to be
	 *        provided.
	 * @param {string} [rank] Only return claims of this rank
	 * @param {string} [props] Optional parts of the claims to return
	 * @return {jQuery.Promise}
	 */
	getClaims: function( entityId, propertyId, claimGuid, rank, props ) {
		var params = {
			action: 'wbgetclaims',
			entity: entityId,
			property: propertyId,
			claim: claimGuid,
			rank: rank,
			props: props
		};

		return this._api.get( params );
	},

	/**
	 * Changes the Main Snak of an existing claim.
	 *
	 * @param {string} claimGuid The GUID of the Claim to be changed (wb.datamodel.Claim.getGuid)
	 * @param {number} baseRevId
	 * @param {string} snakType The type of the snak
	 * @param {string} property Id of the snak's property
	 * @param {object} value The value to set the datavalue of the the main snak of the claim to
	 * @return {jQuery.Promise}
	 */
	setClaimValue: function( claimGuid, baseRevId, snakType, property, value ) {
		var params = {
			action: 'wbsetclaimvalue',
			claim: claimGuid,
			baserevid: baseRevId,
			snaktype: snakType,
			// NOTE: currently 'wbsetclaimvalue' API allows to change snak type but not property,
			//  set it anyhow. The abstracted API won't propagate the API warning we will get here.
			property: property
		};

		if ( value ) {
			params.value = JSON.stringify( value );
		}

		return this.post( params );
	},

	/**
	 * Adds a new or updates an existing Reference of a Statement.
	 *
	 * @since 0.4
	 *
	 * @param {string} statementGuid
	 * @param {object} snaks
	 * @param {number} baseRevId
	 * @param {string} [referenceHash] A hash of the reference that should be updated.
	 *        If not provided, a new reference is created.
	 * @param {number} [index] The reference index. Only needs to be specified if the reference's
	 *        index within the list of all references of the parent statement shall be changed or
	 *        when the reference should be inserted at a specific position.
	 * @return {jQuery.Promise}
	 */
	setReference: function( statementGuid, snaks, baseRevId, referenceHash, index ) {
		var params = {
			action: 'wbsetreference',
			statement: statementGuid,
			snaks: JSON.stringify( snaks ),
			baserevid: baseRevId
		};

		if( index === undefined && typeof referenceHash === 'number' ) {
			index = referenceHash;
			referenceHash = undefined;
		}

		if( referenceHash ) {
			params.reference = referenceHash;
		}

		if( index !== undefined ) {
			params.index = index;
		}

		return this.post( params );
	},

	/**
	 * Will remove one or more existing References of a Statement.
	 *
	 * @since 0.4
	 *
	 * @param {string} statementGuid
	 * @param {string|string[]} referenceHashes One or more hashes of the References to be removed.
	 * @param {number} baseRevId
	 * @return {jQuery.Promise}
	 */
	removeReferences: function( statementGuid, referenceHashes, baseRevId ) {
		var params = {
			action: 'wbremovereferences',
			statement: statementGuid,
			references: this._normalizeParam( referenceHashes ),
			baserevid: baseRevId
		};

		return this.post( params );
	},

	/**
	 * Sets a site link for an item via the API.
	 *
	 * @param {String} id entity id
	 * @param {Number} baseRevId revision id
	 * @param {String} site the site of the link
	 * @param {String} title the title to link to
	 * @param {String[]|String} badges the list of badges
	 * @return {jQuery.Promise}
	 */
	setSitelink: function( id, baseRevId, site, title, badges ) {
		var params = {
			action: 'wbsetsitelink',
			id: id,
			linksite: site,
			linktitle: title,
			baserevid: baseRevId
		};

		if ( badges ) {
			params.badges = this._normalizeParam( badges );
		}

		return this.post( params );
	},

	/**
	 * Sets a site link for an item via the API.
	 *
	 * @param {string} [fromId] The id to merge from
	 * @param {string} [toId] The id to merge to
	 * @param {string[]|string} [ignoreConflicts] Elements of the item to ignore conflicts for
	 * @param {string} [summary] Summary for the edit
	 *
	 * @return {jQuery.Promise}
	 */
	mergeItems: function( fromId, toId, ignoreConflicts, summary ) {
		var params = {
			action: 'wbmergeitems',
			fromid: fromId,
			toid: toId
		};

		if ( ignoreConflicts ) {
			params.ignoreconflicts = this._normalizeParam( ignoreConflicts );
		}

		if ( summary ) {
			params.summary = summary;
		}

		return this.post( params );
	},

	/**
	 * Converts the given value into a string usable by the API
	 *
	 * @since 0.4
	 *
	 * @param {Mixed} value
	 * @return {string|undefined}
	 */
	_normalizeParam: function( value ) {
		return $.isArray( value ) ? value.join( '|' ) : ( value || undefined );
	},

	/**
	 * Submits the AJAX request to the API of the repo and triggers on the response. This will
	 * automatically add the required 'token' information for editing into the given parameters
	 * sent to the API.
	 * @see mw.Api.post
	 *
	 * @param {Object} params parameters for the API call
	 * @return {jQuery.Promise}
	 *         Resolved parameters:
	 *         - {*}
	 *         Rejected parameters:
	 *         - {string}
	 *         - {*}
	 *
	 * @throws {Error} If a parameter is not specified properly
	 */
	post: function( params ) {
		// Unconditionally set the bot parameter to match the UI behaviour of core
		params.bot = 1;

		$.each( params, function( key, value ) {
			if ( value === undefined || value === null ) {
				throw new Error( 'Parameter "' + key + '" is not specified properly.' );
			}
		} );

		return this._api.postWithToken( 'edit', params );
	},

	/**
	 * Performs an API get request.
	 * @see mw.Api.get
	 * @since 0.3
	 *
	 * @param {Object} params
	 * @return {jQuery.Promise}
	 *         Resolved parameters:
	 *         - {*}
	 *         Rejected parameters:
	 *         - {string}
	 *         - {*}
	 */
	'get': function( params ) {
		return this._api.get( params );
	}
} );

}( mediaWiki, wikibase, jQuery ) );

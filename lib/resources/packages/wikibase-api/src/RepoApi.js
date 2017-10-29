( function( wb, $ ) {
'use strict';

var MODULE = wb.api;

/**
 * Constructor to create an API object for interaction with the repo Wikibase API.
 * Functions of `wikibase.api.RepoApi` act on serializations. Before passing native
 * `wikibase.datamodel` objects to a function, such objects need to be serialized, just like return
 * values of `wikibase.api.RepoApi` may be used to construct `wikibase.datamodel` objects.
 * @see wikibase.datamodel
 * @see wikibase.serialization
 *
 * @class wikibase.api.RepoApi
 * @since 1.0
 * @license GPL-2.0+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author Tobias Gritschacher
 * @author H. Snater < mediawiki@snater.com >
 * @author Marius Hoch < hoo@online.de >
 *
 * @constructor
 *
 * @param {mediaWiki.Api} api
 *
 * @throws {Error} if no `mediaWiki.Api` instance is provided.
 */
var SELF = MODULE.RepoApi = function WbApiRepoApi( api ) {
	if( api === undefined ) {
		throw new Error( 'mediaWiki.Api instance needs to be provided' );
	}

	this._api = api;
};

$.extend( SELF.prototype, {
	/**
	 * @property {mediaWiki.Api}
	 * @private
	 */
	_api: null,

	/**
	 * Creates a new entity with the given type and data.
	 * @see wikibase.api.RepoApi._post
	 *
	 * @param {string} type The type of the `Entity` that should be created.
	 * @param {Object} [data={}] The `Entity` data (may be omitted to create an empty `Entity`).
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {*} return.done.result
	 * @return {jqXHR} return.done.jqXHR
	 * @return {Function} return.fail
	 * @return {string} return.fail.code
	 * @return {*} return.fail.error
	 *
	 * @throws {Error} if a parameter is not specified properly.
	 */
	createEntity: function( type, data ) {
		if( typeof type !== 'string' || data && typeof data !== 'object' ) {
			throw new Error( 'Parameter not specified properly' );
		}

		var params = {
			action: 'wbeditentity',
			'new': type,
			data: JSON.stringify( data || {} )
		};
		return this._post( params );
	},

	/**
	 * Edits an `Entity`.
	 * @see wikibase.api.RepoApi._post
	 *
	 * @param {string} id `Entity` id.
	 * @param {number} baseRevId Revision id the edit shall be performed on.
	 * @param {Object} data The `Entity`'s structure.
	 * @param {boolean} [clear] Whether to clear whole entity before editing.
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {*} return.done.result
	 * @return {jqXHR} return.done.jqXHR
	 * @return {Function} return.fail
	 * @return {string} return.fail.code
	 * @return {*} return.fail.error
	 *
	 * @throws {Error} if a parameter is not specified properly.
	 */
	editEntity: function( id, baseRevId, data, clear ) {
		if( typeof id !== 'string'
			|| typeof baseRevId !== 'number'
			|| typeof data !== 'object'
			|| clear && typeof clear !== 'boolean'
		) {
			throw new Error( 'Parameter not specified properly' );
		}

		var params = {
			action: 'wbeditentity',
			id: id,
			baserevid: baseRevId,
			data: JSON.stringify( data )
		};

		if( clear ) {
			params.clear = clear;
		}

		return this._post( params );
	},

	/**
	 * Formats values (`dataValues.DataValue`s).
	 * @see wikibase.api.RepoApi._post
	 *
	 * @param {Object} dataValue `DataValue` serialization.
	 * @param {Object} [options]
	 * @param {string} [dataType] `dataTypes.DataType` id.
	 * @param {string} [outputFormat]
	 * @param {string} [propertyId] replaces `dataType`
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {*} return.done.result
	 * @return {jqXHR} return.done.jqXHR
	 * @return {Function} return.fail
	 * @return {string} return.fail.code
	 * @return {*} return.fail.error
	 *
	 * @throws {Error} if a parameter is not specified properly.
	 */
	formatValue: function( dataValue, options, dataType, outputFormat, propertyId ) {
		if( typeof dataValue !== 'object'
			|| options && typeof options !== 'object'
			|| dataType && typeof dataType !== 'string'
			|| outputFormat && typeof outputFormat !== 'string'
			|| propertyId && typeof propertyId !== 'string'
		) {
			throw new Error( 'Parameter not specified properly' );
		}

		var params = {
			action: 'wbformatvalue',
			datavalue: JSON.stringify( dataValue )
		};

		if( options ) {
			params.options = JSON.stringify( options );
		}

		if( outputFormat ) {
			params.generate = outputFormat;
		}

		if( propertyId ) {
			params.property = propertyId;
		} else if( dataType ) {
			params.datatype = dataType;
		}

		return this._api.get( params );
	},

	/**
	 * Gets one or more `Entity`s.
	 * @see wikibase.api.RepoApi._post
	 *
	 * @param {string|string[]} ids `Entity` id(s).
	 * @param {string|string[]|null} [props] Key(s) of property/ies to retrieve from the API.
	 *        Omitting/`null` will return all properties.
	 * @param {string|string[]|null} [languages] Language code(s) of the languages the
	 *        property/ies values should be retrieved in. Omitting/`null` returns values in all
	 *        languages.
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {*} return.done.result
	 * @return {jqXHR} return.done.jqXHR
	 * @return {Function} return.fail
	 * @return {string} return.fail.code
	 * @return {*} return.fail.error
	 *
	 * @throws {Error} if a parameter is not specified properly.
	 */
	getEntities: function( ids, props, languages ) {
		if( ( typeof ids !== 'string' && !Array.isArray( ids ) )
			|| props && ( typeof props !== 'string' && !Array.isArray( props ) )
			|| languages && ( typeof languages !== 'string' && !Array.isArray( languages ) )
		) {
			throw new Error( 'Parameter not specified properly' );
		}

		var params = {
			action: 'wbgetentities',
			ids: this.normalizeMultiValue( ids )
		};

		if( props ) {
			params.props = this.normalizeMultiValue( props );
		}

		if( languages ) {
			params.languages = this.normalizeMultiValue( languages );
		}

		return this._api.get( params );
	},

	/**
	 * Gets an `Entity` which is linked with on or more specific sites/pages.
	 * @see wikibase.api.RepoApi._post
	 *
	 * @param {string|string[]} sites `Site`(s). May be used with `titles`. May not be a list when
	 *        `titles` is a list.
	 * @param {string|string[]} titles Linked page(s). May be used with `sites`. May not be a list
	 *        when `sites` is a list.
	 * @param {string|string[]|null} [props] Key(s) of property/ies to retrieve from the API.
	 *        Omitting/`null` returns all properties.
	 * @param {string|string[]|null} [languages] Language code(s) of the languages the
	 *        property/ies values should be retrieved in. Omitting/`null` returns values in all
	 *        languages.
	 * @param {boolean} [normalize] Whether to normalize titles server side
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {*} return.done.result
	 * @return {jqXHR} return.done.jqXHR
	 * @return {Function} return.fail
	 * @return {string} return.fail.code
	 * @return {*} return.fail.error
	 *
	 * @throws {Error} if a parameter is not specified properly.
	 * @throws {Error} if both, `sites` and `titles`, are passed as `array`s.
	 */
	getEntitiesByPage: function( sites, titles, props, languages, normalize ) {
		if( ( typeof sites !== 'string' && !Array.isArray( sites ) )
			|| ( typeof titles !== 'string' && !Array.isArray( titles ) )
			|| props && ( typeof props !== 'string' && !Array.isArray( props ) )
			|| languages && ( typeof languages !== 'string' && !Array.isArray( languages ) )
		) {
			throw new Error( 'Parameter not specified properly' );
		}

		if( Array.isArray( sites ) && Array.isArray( titles ) ) {
			throw new Error( 'sites and titles may not be passed as arrays at the same time' );
		}

		var params = {
			action: 'wbgetentities',
			sites: this.normalizeMultiValue( sites ),
			titles: this.normalizeMultiValue( titles ),
			normalize: typeof normalize === 'boolean' ? normalize : undefined
		};

		if( props ) {
			params.props = this.normalizeMultiValue( props );
		}

		if( languages ) {
			params.languages = this.normalizeMultiValue( languages );
		}

		return this._api.get( params );
	},

	/**
	 * Parses values (`dataValues.DataValue`s).
	 * @see wikibase.api.RepoApi._post
	 *
	 * @param {string} parser Parser id.
	 * @param {string[]} values `DataValue` serializations.
	 * @param {Object} [options]
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {*} return.done.result
	 * @return {jqXHR} return.done.jqXHR
	 * @return {Function} return.fail
	 * @return {string} return.fail.code
	 * @return {*} return.fail.error
	 *
	 * @throws {Error} if a parameter is not specified properly.
	 */
	parseValue: function( parser, values, options ) {
		if( typeof parser !== 'string'
			|| !Array.isArray( values )
			|| options && typeof options !== 'object'
		) {
			throw new Error( 'Parameter not specified properly' );
		}

		var params = {
			action: 'wbparsevalue',
			parser: parser,
			values: this.normalizeMultiValue( values )
		};

		if( options ) {
			params.options = JSON.stringify( options );
		}

		return this._api.get( params );
	},

	/**
	 * Searches for `Entity`s containing the given text.
	 * @see wikibase.api.RepoApi._post
	 *
	 * @param {string} search Text to search for.
	 * @param {string} language Language code of the language to search in.
	 * @param {string} type `Entity` type to search for.
	 * @param {number} [limit] Maximum number of results to return.
	 * @param {number} [offset] Offset where to continue returning the search results.
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {*} return.done.result
	 * @return {jqXHR} return.done.jqXHR
	 * @return {Function} return.fail
	 * @return {string} return.fail.code
	 * @return {*} return.fail.error
	 *
	 * @throws {Error} if a parameter is not specified properly.
	 */
	searchEntities: function( search, language, type, limit, offset ) {
		if( typeof search !== 'string'
			|| typeof language !== 'string'
			|| typeof type !== 'string'
		) {
			throw new Error( 'Parameter not specified properly' );
		}

		var params = {
			action: 'wbsearchentities',
			search: search,
			language: language,
			uselang: language,
			type: type,
			limit: typeof limit === 'number' ? limit : undefined,
			'continue': typeof offset === 'number' ? offset : undefined
		};

		return this._api.get( params );
	},

	/**
	 * Sets the label of an `Entity`.
	 * @see wikibase.api.RepoApi._post
	 *
	 * @param {string} id `Entity` id.
	 * @param {number} baseRevId Revision id the edit shall be performed on.
	 * @param {string} label New label text.
	 * @param {string} language Language code of the language the new label should be set in.
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {*} return.done.result
	 * @return {jqXHR} return.done.jqXHR
	 * @return {Function} return.fail
	 * @return {string} return.fail.code
	 * @return {*} return.fail.error
	 *
	 * @throws {Error} if a parameter is not specified properly.
	 */
	setLabel: function( id, baseRevId, label, language ) {
		if( typeof id !== 'string'
			|| typeof baseRevId !== 'number'
			|| typeof label !== 'string'
			|| typeof language !== 'string'
		) {
			throw new Error( 'Parameter not specified properly' );
		}

		var params = {
			action: 'wbsetlabel',
			id: id,
			value: label,
			language: language,
			baserevid: baseRevId
		};

		return this._post( params );
	},

	/**
	 * Sets the description of an `Entity`.
	 * @see wikibase.api.RepoApi._post
	 *
	 * @param {string} id `Entity` id.
	 * @param {number} baseRevId Revision id the edit shall be performed on.
	 * @param {string} description New description text.
	 * @param {string} language Language code of the language the new description should be set in.
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {*} return.done.result
	 * @return {jqXHR} return.done.jqXHR
	 * @return {Function} return.fail
	 * @return {string} return.fail.code
	 * @return {*} return.fail.error
	 *
	 * @throws {Error} if a parameter is not specified properly.
	 */
	setDescription: function( id, baseRevId, description, language ) {
		if( typeof id !== 'string'
			|| typeof baseRevId !== 'number'
			|| typeof description !== 'string'
			|| typeof language !== 'string'
		) {
			throw new Error( 'Parameter not specified properly' );
		}

		var params = {
			action: 'wbsetdescription',
			id: id,
			value: description,
			language: language,
			baserevid: baseRevId
		};

		return this._post( params );
	},

	/**
	 * Adds and/or remove a number of aliases of an `Entity`.
	 * @see wikibase.api.RepoApi._post
	 *
	 * @param {string} id `Entity` id.
	 * @param {number} baseRevId Revision id the edit shall be performed on.
	 * @param {string[]} add Aliases to add.
	 * @param {string[]} remove Aliases to remove.
	 * @param {string} language Language code of the language the aliases should be added/removed
	 *        in.
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {*} return.done.result
	 * @return {jqXHR} return.done.jqXHR
	 * @return {Function} return.fail
	 * @return {string} return.fail.code
	 * @return {*} return.fail.error
	 *
	 * @throws {Error} if a parameter is not specified properly.
	 */
	setAliases: function( id, baseRevId, add, remove, language ) {
		if( typeof id !== 'string'
			|| typeof baseRevId !== 'number'
			|| !Array.isArray( add )
			|| !Array.isArray( remove )
			|| typeof language !== 'string'
		) {
			throw new Error( 'Parameter not specified properly' );
		}

		var params = {
			action: 'wbsetaliases',
			id: id,
			add: this.normalizeMultiValue( add ),
			remove: this.normalizeMultiValue( remove ),
			language: language,
			baserevid: baseRevId
		};

		return this._post( params );
	},

	/**
	 * Creates/Updates an entire `Claim`.
	 * @see wikibase.api.RepoApi._post
	 *
	 * @param {Object} claim `Claim` serialization.
	 * @param {number} baseRevId Revision id the edit shall be performed on.
	 * @param {number} [index] The `Claim`'s index. Only needs to be specified if the `Claim`'s
	 *        index within the list of all `Claim`s of the parent `Entity` shall be changed.
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {*} return.done.result
	 * @return {jqXHR} return.done.jqXHR
	 * @return {Function} return.fail
	 * @return {string} return.fail.code
	 * @return {*} return.fail.error
	 *
	 * @throws {Error} if a parameter is not specified properly.
	 */
	setClaim: function( claim, baseRevId, index ) {
		if( typeof claim !== 'object' || typeof baseRevId !== 'number' ) {
			throw new Error( 'Parameter not specified properly' );
		}

		var params = {
			action: 'wbsetclaim',
			claim: JSON.stringify( claim ),
			baserevid: baseRevId
		};

		if( typeof index === 'number' ) {
			params.index = index;
		}

		return this._post( params );
	},

	/**
	 * Creates a `Claim`.
	 * @see wikibase.api.RepoApi._post
	 *
	 * @param {string} entityId `Entity` id.
	 * @param {number} baseRevId Revision id the edit shall be performed on.
	 * @param {string} snakType The type of the `Snak` (see `wikibase.datamodel.Snak.TYPE`).
	 * @param {string} propertyId Id of the `Snak`'s `Property`.
	 * @param {Object|string} [value] `DataValue` serialization that needs to be provided when the
	 *        specified `Snak` type requires a value.
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {*} return.done.result
	 * @return {jqXHR} return.done.jqXHR
	 * @return {Function} return.fail
	 * @return {string} return.fail.code
	 * @return {*} return.fail.error
	 *
	 * @throws {Error} if a parameter is not specified properly.
	 */
	createClaim: function( entityId, baseRevId, snakType, propertyId, value ) {
		if( typeof entityId !== 'string'
			|| typeof baseRevId !== 'number'
			|| typeof snakType !== 'string'
			|| typeof propertyId !== 'string'
			|| value && typeof value !== 'string' && typeof value !== 'object'
		) {
			throw new Error( 'Parameter not specified properly' );
		}

		var params = {
			action: 'wbcreateclaim',
			entity: entityId,
			baserevid: baseRevId,
			snaktype: snakType,
			property: propertyId
		};

		if( value ) {
			params.value = JSON.stringify( value );
		}

		return this._post( params );
	},

	/**
	 * Removes a `Claim`.
	 * @see wikibase.api.RepoApi._post
	 *
	 * @param {string} claimGuid The GUID of the `Claim` to be removed.
	 * @param {number} [claimRevisionId] Revision id the edit shall be performed on.
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {*} return.done.result
	 * @return {jqXHR} return.done.jqXHR
	 * @return {Function} return.fail
	 * @return {string} return.fail.code
	 * @return {*} return.fail.error
	 *
	 * @throws {Error} if a parameter is not specified properly.
	 */
	removeClaim: function( claimGuid, claimRevisionId ) {
		if( typeof claimGuid !== 'string'
			|| claimRevisionId && typeof claimRevisionId !== 'number'
		) {
			throw new Error( 'Parameter not specified properly' );
		}

		var params = {
			action: 'wbremoveclaims',
			claim: claimGuid
		};

		if( claimRevisionId ) {
			params.baserevid = claimRevisionId;
		}

		return this._post( params );
	},

	/**
	 * Returns `Claim`s of a specific `Entity` by providing an `Entity` id or a specific `Claim` by
	 * providing a `Claim` GUID.
	 * @see wikibase.api.RepoApi._post
	 *
	 * @param {string|null} entityId `Entity` id. May be `null` if `claimGuid` is specified.
	 * @param {string} [propertyId] Only return `Claim`s featuring this `Property`.
	 * @param {string} [claimGuid] GUID of the `Claim` to return. Either `claimGuid` or `entityID`
	 *        has to be provided.
	 * @param {string} [rank] Only return `Claim`s of this `rank`.
	 * @param {string} [props] Specific parts of the `Claim`s to include in the response.
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {*} return.done.result
	 * @return {jqXHR} return.done.jqXHR
	 * @return {Function} return.fail
	 * @return {string} return.fail.code
	 * @return {*} return.fail.error
	 *
	 * @throws {Error} if a parameter is not specified properly.
	 * @throws {Error} if neither `entityId` nor `claimGuid` is provided.
	 */
	getClaims: function( entityId, propertyId, claimGuid, rank, props ) {
		if( entityId && typeof entityId !== 'string'
			|| propertyId && typeof propertyId !== 'string'
			|| claimGuid && typeof claimGuid !== 'string'
			|| rank && typeof rank !== 'string'
			|| props && typeof props !== 'string'
		) {
			throw new Error( 'Parameter not specified properly' );
		}

		if( !entityId && !claimGuid ) {
			throw new Error( 'Either entity id or claim GUID needs to be provided.' );
		}

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
	 * Changes the main `Snak` of an existing `Claim`.
	 * @see wikibase.api.RepoApi._post
	 *
	 * @param {string} claimGuid The GUID of the `Claim` to be changed.
	 * @param {number} baseRevId Revision id the edit shall be performed on.
	 * @param {string} snakType The type of the `Snak` (see `wikibase.datamodel.Snak.TYPE`).
	 * @param {string} propertyId Id of the `Snak`'s `Property`.
	 * @param {Object|string} [value] `DataValue` serialization that needs to be provided when the
	 *        specified `Snak` type requires a value.
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {*} return.done.result
	 * @return {jqXHR} return.done.jqXHR
	 * @return {Function} return.fail
	 * @return {string} return.fail.code
	 * @return {*} return.fail.error
	 *
	 * @throws {Error} if a parameter is not specified properly.
	 */
	setClaimValue: function( claimGuid, baseRevId, snakType, propertyId, value ) {
		if( typeof claimGuid !== 'string'
			|| typeof baseRevId !== 'number'
			|| typeof snakType !== 'string'
			|| typeof propertyId !== 'string'
			|| value && typeof value !== 'string' && typeof value !== 'object'
		) {
			throw new Error( 'Parameter not specified properly' );
		}

		var params = {
			action: 'wbsetclaimvalue',
			claim: claimGuid,
			baserevid: baseRevId,
			snaktype: snakType,
			// NOTE: currently 'wbsetclaimvalue' API allows to change snak type but not property,
			//  set it anyhow. The abstracted API won't propagate the API warning we will get here.
			property: propertyId
		};

		if( value ) {
			params.value = JSON.stringify( value );
		}

		return this._post( params );
	},

	/**
	 * Adds a new or updates an existing `Reference` of a `Statement`.
	 * @see wikibase.api.RepoApi._post
	 *
	 * @param {string} statementGuid `GUID` of the `Statement` which a `Reference`.
	 *        should be added to or changed of.
	 * @param {Object} snaks `snak` portion of a serialized `Reference`.
	 * @param {number} baseRevId Revision id the edit shall be performed on.
	 * @param {string|number} [referenceHash] A hash of the reference that should be updated.
	 *        If not provided, a new reference is created.
	 *        Assumed to be `index` if of type `number`.
	 * @param {number} [index] The `Reference` index. Only needs to be specified if the
	 *        `Reference`'s index within the list of all `Reference`s of the parent `Statement`
	 *        shall be changed or when the `Reference` should be inserted at a specific position.
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {*} return.done.result
	 * @return {jqXHR} return.done.jqXHR
	 * @return {Function} return.fail
	 * @return {string} return.fail.code
	 * @return {*} return.fail.error
	 *
	 * @throws {Error} if a parameter is not specified properly.
	 */
	setReference: function( statementGuid, snaks, baseRevId, referenceHash, index ) {
		if( index === undefined && typeof referenceHash === 'number' ) {
			index = referenceHash;
			referenceHash = undefined;
		}

		if( typeof statementGuid !== 'string'
			|| typeof snaks !== 'object'
			|| typeof baseRevId !== 'number'
			|| referenceHash && typeof referenceHash !== 'string'
			|| index && typeof index !== 'number'
		) {
			throw new Error( 'Parameter not specified properly' );
		}

		var params = {
			action: 'wbsetreference',
			statement: statementGuid,
			snaks: JSON.stringify( snaks ),
			baserevid: baseRevId
		};

		if( referenceHash ) {
			params.reference = referenceHash;
		}

		if( index !== undefined ) {
			params.index = index;
		}

		return this._post( params );
	},

	/**
	 * Removes one or more `Reference`s of a `Statement`.
	 * @see wikibase.api.RepoApi._post
	 *
	 * @param {string} statementGuid `GUID` of the `Statement` which `Reference`s should be removed
	 *        of.
	 * @param {string|string[]} referenceHashes One or more hashes of the `Reference`s to be
	 *        removed.
	 * @param {number} baseRevId Revision id the edit shall be performed on.
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {*} return.done.result
	 * @return {jqXHR} return.done.jqXHR
	 * @return {Function} return.fail
	 * @return {string} return.fail.code
	 * @return {*} return.fail.error
	 *
	 * @throws {Error} if a parameter is not specified properly.
	 */
	removeReferences: function( statementGuid, referenceHashes, baseRevId ) {
		if( typeof statementGuid !== 'string'
			|| typeof referenceHashes !== 'string' && !Array.isArray( referenceHashes )
			|| typeof baseRevId !== 'number'
		) {
			throw new Error( 'Parameter not specified properly' );
		}

		var params = {
			action: 'wbremovereferences',
			statement: statementGuid,
			references: this.normalizeMultiValue( referenceHashes ),
			baserevid: baseRevId
		};

		return this._post( params );
	},

	/**
	 * Sets a `SiteLink` for an item via the API.
	 * @see wikibase.api.RepoApi._post
	 *
	 * @param {string} id `Entity` id.
	 * @param {number} baseRevId Revision id the edit shall be performed on.
	 * @param {string} site Site of the link.
	 * @param {string} title Title to link to.
	 * @param {string[]|string} [badges] List of `Entity` ids to be assigned as badges.
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {*} return.done.result
	 * @return {jqXHR} return.done.jqXHR
	 * @return {Function} return.fail
	 * @return {string} return.fail.code
	 * @return {*} return.fail.error
	 *
	 * @throws {Error} if a parameter is not specified properly.
	 */
	setSitelink: function( id, baseRevId, site, title, badges ) {
		if( typeof id !== 'string'
			|| typeof baseRevId !== 'number'
			|| typeof site !== 'string'
			|| typeof title !== 'string'
			|| badges && typeof badges !== 'string' && !Array.isArray( badges )
		) {
			throw new Error( 'Parameter not specified properly' );
		}

		var params = {
			action: 'wbsetsitelink',
			id: id,
			linksite: site,
			linktitle: title,
			baserevid: baseRevId
		};

		if( badges ) {
			params.badges = this.normalizeMultiValue( badges );
		}

		return this._post( params );
	},

	/**
	 * Sets a site link for an item via the API.
	 * @see wikibase.api.RepoApi._post
	 *
	 * @param {string} fromId `Entity` id to merge from.
	 * @param {string} toId `Entity` id to merge to.
	 * @param {string[]|string} [ignoreConflicts] Elements of the `Item` to ignore conflicts for.
	 * @param {string} [summary] Summary for the edit.
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {*} return.done.result
	 * @return {jqXHR} return.done.jqXHR
	 * @return {Function} return.fail
	 * @return {string} return.fail.code
	 * @return {*} return.fail.error
	 *
	 * @throws {Error} if a parameter is not specified properly.
	 */
	mergeItems: function( fromId, toId, ignoreConflicts, summary ) {
		if( typeof fromId !== 'string'
			|| typeof toId !== 'string'
			|| ignoreConflicts
				&& typeof ignoreConflicts !== 'string'
				&& !Array.isArray( ignoreConflicts )
			|| summary && typeof summary !== 'string'
		) {
			throw new Error( 'Parameter not specified properly' );
		}

		var params = {
			action: 'wbmergeitems',
			fromid: fromId,
			toid: toId
		};

		if( ignoreConflicts ) {
			params.ignoreconflicts = this.normalizeMultiValue( ignoreConflicts );
		}

		if( summary ) {
			params.summary = summary;
		}

		return this._post( params );
	},

	/**
	 * Converts the given value into a string usable by the API.
	 * @private
	 *
	 * @param {string[]|string|null} [value]
	 * @return {string}
	 */
	normalizeMultiValue: function ( value ) {
		if ( Array.isArray( value ) ) {
			value = value.join( '\x1f' );
		}

		// We must enforce the alternative separation character, see ApiBase.php::explodeMultiValue.
		return value ? '\x1f' + value : '';
	},

	/**
	 * Submits the AJAX request to the API of the repo and triggers on the response. This will
	 * automatically add the required 'token' information for editing into the given parameters
	 * sent to the API.
	 * @see mediaWiki.Api.post
	 * @see mediaWiki.Api.ajax
	 * @private
	 *
	 * @param {Object} params parameters for the API call.
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {*} return.done.result
	 * @return {jqXHR} return.done.jqXHR
	 * @return {Function} return.fail
	 * @return {string} return.fail.code
	 * @return {*} return.fail.error A plain object with information about the error if `code` is
	 *         "http", a string, if the call was successful but the response is empty or the result
	 *         result if it contains an `error` field.
	 *
	 * @throws {Error} if a parameter is not specified properly.
	 */
	_post: function( params ) {
		// Unconditionally set the bot parameter to match the UI behaviour of core
		params.bot = 1;

		$.each( params, function( key, value ) {
			if ( value === undefined || value === null ) {
				throw new Error( 'Parameter "' + key + '" is not specified properly.' );
			}
		} );

		return this._api.postWithToken( 'csrf', params );
	}
} );

}( wikibase, jQuery ) );

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
 * @licence GNU GPL v2+
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
var SELF = MODULE.RepoApi = function wbRepoApi( api ) {
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
	 */
	createEntity: function( type, data ) {
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
	 * @param {String} id `Entity` id.
	 * @param {Number} baseRevId Revision id the edit shall be performed on.
	 * @param {Object} data The `Entity`'s structure.
	 * @param {Boolean} [clear=false] Whether to clear whole entity before editing.
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {*} return.done.result
	 * @return {jqXHR} return.done.jqXHR
	 * @return {Function} return.fail
	 * @return {string} return.fail.code
	 * @return {*} return.fail.error
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

		return this._post( params );
	},

	/**
	 * Formats values (`dataValues.DataValue`s).
	 * @see wikibase.api.RepoApi._post
	 *
	 * @param {Object} dataValue `DataValue` serialization.
	 * @param {Object} [options={}]
	 * @param {string} [dataType] `dataTypes.DataType` id.
	 * @param {string} [outputFormat]
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {*} return.done.result
	 * @return {jqXHR} return.done.jqXHR
	 * @return {Function} return.fail
	 * @return {string} return.fail.code
	 * @return {*} return.fail.error
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
	 * Gets one or more `Entity`s.
	 * @see wikibase.api.RepoApi._post
	 *
	 * @param {string|string[]} ids `Entity` id(s).
	 * @param {string|string[]|null} [props=null] Key(s) of property/ies to retrieve from the API.
	 *        `null` will return all properties.
	 * @param {string|string[]|null} [languages=null] Language code(s) of the languages the
	 *        property/ies values should be retrieved in. `null` returns values in all languages.
	 * @param {string|string[]|null} [sort=null] Key(s) of property/ies to sort on. `null` will
	 *        result in unsorted output.
	 * @param {string|null} [dir=null] Sort direction, may be 'ascending' or 'descending'.
	 *        `null` resolves to 'ascending'.
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {*} return.done.result
	 * @return {jqXHR} return.done.jqXHR
	 * @return {Function} return.fail
	 * @return {string} return.fail.code
	 * @return {*} return.fail.error
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
	 * Gets an `Entity` which is linked with on or more specific sites/pages.
	 * @see wikibase.api.RepoApi._post
	 *
	 * @param {string|string[]} [sites] `Site`(s). May be used with `titles`. May not be a list when
	 *        `titles` is a list.
	 * @param {string|string[]} [titles] Linked page(s). May be used with `sites`. May not be a list
	 *        when `sites` is a list.
	 * @param {string|string[]|null} [props=null] Key(s) of property/ies to retrieve from the API.
	 *        `null` returns all properties.
	 * @param {string|string[]|null} [languages=null] Language code(s) of the languages the
	 *        property/ies values should be retrieved in. `null` returns values in all languages.
	 * @param {string|string[]|null} [sort=null] Key(s) of property/ies to sort on. `null` will
	 *        result in unsorted output.
	 * @param {string|null} [dir=null] Sort direction, may be 'ascending' or 'descending'.
	 *        `null` resolves to 'ascending'.
	 * @param {boolean} [normalize] Whether to normalize titles server side
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {*} return.done.result
	 * @return {jqXHR} return.done.jqXHR
	 * @return {Function} return.fail
	 * @return {string} return.fail.code
	 * @return {*} return.fail.error
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
			normalize: typeof normalize === 'boolean' ? normalize : undefined
		};

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
	 * Searches for `Entity`s containing the given text.
	 * @see wikibase.api.RepoApi._post
	 *
	 * @param {string} search Text to search for.
	 * @param {string} language Language code of the language to search in.
	 * @param {string} type `Entity` type to search for.
	 * @param {number} limit Maximum number of results to return.
	 * @param {number} offset Offset where to continue returning the search results.
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {*} return.done.result
	 * @return {jqXHR} return.done.jqXHR
	 * @return {Function} return.fail
	 * @return {string} return.fail.code
	 * @return {*} return.fail.error
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
	 */
	setLabel: function( id, baseRevId, label, language ) {
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
	 */
	setDescription: function( id, baseRevId, description, language ) {
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
	 * @param {string|string[]} add Alias(es) to add.
	 * @param {string|string[]} remove Alias(es) to remove.
	 * @param {string} language Language code of the language the aliases should be added/removed
	 *        in.
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {*} return.done.result
	 * @return {jqXHR} return.done.jqXHR
	 * @return {Function} return.fail
	 * @return {string} return.fail.code
	 * @return {*} return.fail.error
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

		return this._post( params );
	},

	/**
	 * Creates a `Claim`.
	 * @see wikibase.api.RepoApi._post
	 *
	 * @param {string} entityId `Entity` id.
	 * @param {number} baseRevId Revision id the edit shall be performed on.
	 * @param {string} snakType The type of the `Snak` (see `wikibase.datamodel.Snak.TYPE`).
	 * @param {string} property Id of the `Snak`'s `Property`.
	 * @param {Object|string} [value] `DataValue` serialization that needs to be provided when the
	 *        specified `Snak` type requires a value.
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {*} return.done.result
	 * @return {jqXHR} return.done.jqXHR
	 * @return {Function} return.fail
	 * @return {string} return.fail.code
	 * @return {*} return.fail.error
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
	 */
	removeClaim: function( claimGuid, claimRevisionId ) {
		return this._post( {
			action: 'wbremoveclaims',
			claim: claimGuid,
			baserevid: claimRevisionId
		} );
	},

	/**
	 * Returns `Claim`s of a specific `Entity` by providing an `Entity` id or a specific `Claim` by
	 * providing a `Claim` GUID.
	 * @see wikibase.api.RepoApi._post
	 *
	 * @param {string|null} entityId `Entity` id.
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
	 * Changes the main `Snak` of an existing `Claim`.
	 * @see wikibase.api.RepoApi._post
	 *
	 * @param {string} claimGuid The GUID of the `Claim` to be changed.
	 * @param {number} baseRevId Revision id the edit shall be performed on.
	 * @param {string} snakType The type of the `Snak` (see `wikibase.datamodel.Snak.TYPE`).
	 * @param {string} property Id of the `Snak`'s `Property`.
	 * @param {Object|string} [value] `DataValue` serialization that needs to be provided when the
	 *        specified `Snak` type requires a value.
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {*} return.done.result
	 * @return {jqXHR} return.done.jqXHR
	 * @return {Function} return.fail
	 * @return {string} return.fail.code
	 * @return {*} return.fail.error
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
	 * @param {string} [referenceHash] A hash of the reference that should be updated.
	 *        If not provided, a new reference is created.
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
	 */
	removeReferences: function( statementGuid, referenceHashes, baseRevId ) {
		var params = {
			action: 'wbremovereferences',
			statement: statementGuid,
			references: this._normalizeParam( referenceHashes ),
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

		return this._post( params );
	},

	/**
	 * Sets a site link for an item via the API.
	 * @see wikibase.api.RepoApi._post
	 *
	 * @param {string} [fromId] `Entity` id to merge from.
	 * @param {string} [toId] `Entity` id to merge to.
	 * @param {string[]|string} [ignoreConflicts] Elements of the `Item` to ignore conflicts for.
	 * @param {string} [summary] Summary for the edit.
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {*} return.done.result
	 * @return {jqXHR} return.done.jqXHR
	 * @return {Function} return.fail
	 * @return {string} return.fail.code
	 * @return {*} return.fail.error
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

		return this._post( params );
	},

	/**
	 * Converts the given value into a string usable by the API.
	 * @private
	 *
	 * @param {string[]|string|null} [value]
	 * @return {string|undefined}
	 */
	_normalizeParam: function( value ) {
		return $.isArray( value ) ? value.join( '|' ) : ( value || undefined );
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

		return this._api.postWithToken( 'edit', params );
	}
} );

}( wikibase, jQuery ) );

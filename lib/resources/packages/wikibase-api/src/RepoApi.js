( function ( wb ) {
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
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 * @author Tobias Gritschacher
	 * @author H. Snater < mediawiki@snater.com >
	 * @author Marius Hoch < hoo@online.de >
	 *
	 * @constructor
	 *
	 * @param {mediaWiki.Api} api
	 * @param {string|null} uselang
	 * @param {string[]} [tags] Change tags to add to edits made through this instance.
	 *
	 * @throws {Error} if no `mediaWiki.Api` instance is provided.
	 */
	var SELF = MODULE.RepoApi = function WbApiRepoApi( api, uselang, tags ) {
		if ( api === undefined ) {
			throw new Error( 'mediaWiki.Api instance needs to be provided' );
		}

		this._api = api;
		this._uselang = uselang;
		this._tags = tags || [];
	};

	$.extend( SELF.prototype, {
		/**
		 * @property {mediaWiki.Api}
		 * @private
		 */
		_api: null,

		/**
		 * @property {string|null}
		 * @private
		 */
		_uselang: null,

		/**
		 * @property {string[]}
		 * @private
		 */
		_tags: null,

		/**
		 * Creates a new entity with the given type and data.
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
		createEntity: function ( type, data ) {
			if ( typeof type !== 'string' || data && typeof data !== 'object' ) {
				throw new Error( 'Parameter not specified properly' );
			}

			var params = {
				action: 'wbeditentity',
				new: type,
				data: JSON.stringify( data || {} )
			};

			if ( this._tags.length ) {
				params.tags = this.normalizeMultiValue( this._tags );
			}

			return this.post( params );
		},

		/**
		 * Edits an `Entity`.
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
		editEntity: function ( id, baseRevId, data, clear ) {
			if (
				typeof id !== 'string'
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

			if ( clear ) {
				params.clear = clear;
			}

			if ( this._tags.length ) {
				params.tags = this.normalizeMultiValue( this._tags );
			}

			return this.post( params );
		},

		/**
		 * Formats values (`dataValues.DataValue`s).
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
		formatValue: function ( dataValue, options, dataType, outputFormat, propertyId ) {
			if (
				typeof dataValue !== 'object'
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

			if ( outputFormat ) {
				params.generate = outputFormat;
			}

			if ( propertyId ) {
				params.property = propertyId;
			} else if ( dataType ) {
				params.datatype = dataType;
			}

			return this.get( params, options );
		},

		/**
		 * Gets one or more `Entity`s.
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
		getEntities: function ( ids, props, languages ) {
			if (
				( typeof ids !== 'string' && !Array.isArray( ids ) )
				|| props && ( typeof props !== 'string' && !Array.isArray( props ) )
				|| languages && ( typeof languages !== 'string' && !Array.isArray( languages ) )
			) {
				throw new Error( 'Parameter not specified properly' );
			}

			var params = {
				action: 'wbgetentities',
				ids: this.normalizeMultiValue( ids )
			};

			if ( props ) {
				params.props = this.normalizeMultiValue( props );
			}

			if ( languages ) {
				params.languages = this.normalizeMultiValue( languages );
			}

			return this.get( params );
		},

		/**
		 * Gets an `Entity` which is linked with on or more specific sites/pages.
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
		getEntitiesByPage: function ( sites, titles, props, languages, normalize ) {
			if (
				( typeof sites !== 'string' && !Array.isArray( sites ) )
				|| ( typeof titles !== 'string' && !Array.isArray( titles ) )
				|| props && ( typeof props !== 'string' && !Array.isArray( props ) )
				|| languages && ( typeof languages !== 'string' && !Array.isArray( languages ) )
			) {
				throw new Error( 'Parameter not specified properly' );
			}

			if ( Array.isArray( sites ) && Array.isArray( titles ) ) {
				throw new Error( 'sites and titles may not be passed as arrays at the same time' );
			}

			var params = {
				action: 'wbgetentities',
				sites: this.normalizeMultiValue( sites ),
				titles: this.normalizeMultiValue( titles ),
				normalize: typeof normalize === 'boolean' ? normalize : undefined
			};

			if ( props ) {
				params.props = this.normalizeMultiValue( props );
			}

			if ( languages ) {
				params.languages = this.normalizeMultiValue( languages );
			}

			return this.get( params );
		},

		/**
		 * Parses values (`dataValues.DataValue`s).
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
		parseValue: function ( parser, values, options ) {
			if (
				typeof parser !== 'string'
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

			return this.get( params, options );
		},

		/**
		 * Sets the label of an `Entity`.
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
		setLabel: function ( id, baseRevId, label, language ) {
			if (
				typeof id !== 'string'
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

			if ( this._tags.length ) {
				params.tags = this.normalizeMultiValue( this._tags );
			}

			return this.post( params );
		},

		/**
		 * Sets the description of an `Entity`.
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
		setDescription: function ( id, baseRevId, description, language ) {
			if (
				typeof id !== 'string'
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

			if ( this._tags.length ) {
				params.tags = this.normalizeMultiValue( this._tags );
			}

			return this.post( params );
		},

		/**
		 * Adds and/or remove a number of aliases of an `Entity`.
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
		setAliases: function ( id, baseRevId, add, remove, language ) {
			if (
				typeof id !== 'string'
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

			if ( this._tags.length ) {
				params.tags = this.normalizeMultiValue( this._tags );
			}

			return this.post( params );
		},

		/**
		 * Creates/Updates an entire `Claim`.
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
		setClaim: function ( claim, baseRevId, index ) {
			if ( typeof claim !== 'object' || typeof baseRevId !== 'number' ) {
				throw new Error( 'Parameter not specified properly' );
			}

			var params = {
				action: 'wbsetclaim',
				claim: JSON.stringify( claim ),
				baserevid: baseRevId
			};

			if ( typeof index === 'number' ) {
				params.index = index;
			}

			if ( this._tags.length ) {
				params.tags = this.normalizeMultiValue( this._tags );
			}

			return this.post( params );
		},

		/**
		 * Removes a `Claim`.
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
		removeClaim: function ( claimGuid, claimRevisionId ) {
			if (
				typeof claimGuid !== 'string'
				|| claimRevisionId && typeof claimRevisionId !== 'number'
			) {
				throw new Error( 'Parameter not specified properly' );
			}

			var params = {
				action: 'wbremoveclaims',
				claim: claimGuid
			};

			if ( claimRevisionId ) {
				params.baserevid = claimRevisionId;
			}

			if ( this._tags.length ) {
				params.tags = this.normalizeMultiValue( this._tags );
			}

			return this.post( params );
		},

		/**
		 * Sets a `SiteLink` for an item via the API.
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
		setSitelink: function ( id, baseRevId, site, title, badges ) {
			if ( typeof id !== 'string'
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

			if ( badges ) {
				params.badges = this.normalizeMultiValue( badges );
			}

			if ( this._tags.length ) {
				params.tags = this.normalizeMultiValue( this._tags );
			}

			return this.post( params );
		},

		/**
		 * Sets a site link for an item via the API.
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
		mergeItems: function ( fromId, toId, ignoreConflicts, summary ) {
			if ( typeof fromId !== 'string'
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

			if ( ignoreConflicts ) {
				params.ignoreconflicts = this.normalizeMultiValue( ignoreConflicts );
			}

			if ( summary ) {
				params.summary = summary;
			}

			if ( this._tags.length ) {
				params.tags = this.normalizeMultiValue( this._tags );
			}

			return this.post( params );
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
		 * Submits a GET request to the API with added 'errorformat' and 'uselang' parameters as
		 * well as stringified options.
		 *
		 * @param {Object} params parameters for the API call.
		 * @param {Object} [options]
		 * @return {Object} jQuery.Promise
		 * @return {Function} return.done
		 * @return {*} return.done.result
		 * @return {jqXHR} return.done.jqXHR
		 * @return {Function} return.fail
		 * @return {string} return.fail.code
		 * @return {*} return.fail.error
		 */
		get: function ( params, options ) {
			params.errorformat = 'plaintext';
			if ( this._uselang ) {
				params.uselang = this._uselang;
			}

			if ( options ) {
				params.options = JSON.stringify( options );

				// override 'uselang' parameter if passed via options
				if ( options.lang ) {
					params.uselang = options.lang;
				}
			}

			return this._api.get( params );
		},

		/**
		 * Submits the AJAX request to the API of the repo and triggers on the response. This will
		 * automatically add the required 'token' information for editing into the given parameters
		 * sent to the API. Additionally, it sets the 'errorformat' and 'uselang' parameters.
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
		post: function ( params ) {
			/**
			 * Unconditionally set the bot parameter to match the UI behavior of core.
			 * In normal page editing, if you have the "bot" user right and edit through the GUI
			 * interface, your edit is marked as bot no matter what.
			 * @see https://gerrit.wikimedia.org/r/71246
			 * @see https://phabricator.wikimedia.org/T189477
			 */
			params.bot = 1;

			// assert the api user matches the browser user in case one has logged out after page load.
			if ( !mw.user.isAnon() ) {
				params.assertuser = mw.user.getName();
			}

			params.errorformat = 'plaintext';
			if ( this._uselang ) {
				params.uselang = this._uselang;
			}

			Object.keys( params ).forEach( function ( key ) {
				if ( key === undefined || params[ key ] === null ) {
					throw new Error( 'Parameter "' + key + '" is not specified properly.' );
				}
			} );

			return this._api.postWithToken( 'csrf', params );
		}
	} );

}( wikibase ) );

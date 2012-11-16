/**
 * @file
 * @ingroup Wikibase
 * @licence GNU GPL v2+
 * @author Daniel Werner
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
 * @since 0.2
 */
wb.Api = wb.utilities.inherit( PARENT, {

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
		return this.post( params );
	},

	/**
	 * Edit an entity.
	 *
	 * @param {String} id Entity id
	 * @param {Number} baserevid Revision id the edit shall be performed on
	 * @param {Object} data The entity's structure
	 * @param {Boolean} [clear] Whether to clear whole entity before editing (default: false)
	 * @return {jQuery.Promise}
	 */
	editEntity: function( id, baserevid, data, clear ) {
		var params = {
			action: 'wbeditentity',
			id: id,
			baserevid: baserevid,
			data: $.toJSON( data )
		};

		if ( clear ) {
			params.clear = true;
		}

		return this.post( params );
	},

	/**
	 * Get one or more entities.
	 *
	 * @param {String[]|String} ids
	 * @param {String[]|String} [props] Key(s) of property/ies to retrieve from the API
	 *                          default: null (will return all properties)
	 * @param {String[]|String} [sort] Key(s) of property/ies to sort on
	 *                          default: null (unsorted)
	 * @param {String}          [dir] Sort direction may be 'ascending' or 'descending'
	 *                          default: null (ascending)
	 * @param {String[]}        [languages]
	 *                          default: null (will return results in all languages)
	 * @return {jQuery.Promise}
	 */
	getEntities: function( ids, props, sort, dir, languages ) {
		var params = {
			action: 'wbgetentities',
			ids: ids
		};

		if ( props ) {
			if ( $.isArray( props ) ) {
				props.join( '|' );
			}
			params.props = props;
		}

		if ( sort ) {
			if ( $.isArray( sort ) ) {
				sort.join( '|' );
			}
			params.sort = sort;
		}

		if ( dir ) {
			params.dir = dir;
		}

		if ( languages ) {
			if ( $.isArray( languages ) ) {
				languages.join( '|' );
			}
			params.languages = languages;
		}

		return this.post( params );
	},

	/**
	 * Sets the label of an entity via the API.
	 *
	 * @param {String} id entity id
	 * @param {Number} baserevid revision id
	 * @param {String} label the label to set
	 * @param {String} language the language in which the label should be set
	 * @return {jQuery.Promise}
	 */
	setLabel: function( id, baserevid, label, language ) {
		var params = {
			action: "wbsetlabel",
			id: id,
			value: label,
			language: language,
			baserevid: baserevid
		};
		return this.post( params );
	},

	/**
	 * Sets the description of an entity via the API.
	 *
	 * @param {String} id entity id
	 * @param {Number} baserevid revision id
	 * @param {String} description the description to set
	 * @param {String} language the language in which the description should be set
	 * @return {jQuery.Promise}
	 */
	setDescription: function( id, baserevid, description, language ) {
		var params = {
			action: "wbsetdescription",
			id: id,
			value: description,
			language: language,
			baserevid: baserevid
		};
		return this.post( params );
	},

	/**
	 * Sets a site link for an item via the API.
	 *
	 * @param {String} id entity id
	 * @param {Number} baserevid revision id
	 * @param {String} site the site of the link
	 * @param {String} title the title to link to
	 * @return {jQuery.Promise}
	 */
	setSitelink: function( id, baserevid, site, title ) {
		var params = {
			action: "wbsetsitelink",
			id: id,
			linksite: site,
			linktitle: title,
			baserevid: baserevid
		};
		return this.post( params );
	},

	/**
	 * Removes a sitelink of an item via the API.
	 *
	 * @param {String} id entity id
	 * @param {Number} baserevid revision id
	 * @param {String} site the site of the link
	 * @return {jQuery.Promise}
	 */
	removeSitelink: function( id, baserevid, site ) {
		return this.setSitelink( id, baserevid, site, '' );
	},

	/**
	 * Adds and/or remove a number of aliases of an item via the API.
	 *
	 * @param {String} id entity id
	 * @param {Number} baserevid revision id
	 * @param {String[]|String} add Alias(es) to add
	 * @param {String[]|String} remove Alias(es) to remove
	 * @param {String} language the language in which the aliases should be added/removed
	 * @return {jQuery.Promise}
	 */
	setAliases: function( id, baserevid, add, remove, language ) {
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
			baserevid: baserevid
		};
		return this.post( params );
	},

	/**
	 * Submits the AJAX request to query the API and triggers resolving the response.
	 *
	 * @param {Object} params parameters for the API call
	 * @return {jQuery.Promise}
	 */
	post: function( params ) {
		$.extend( params, { token: mw.user.tokens.get( 'editToken' ) } );
		return this._api.post( params );
	}

} );

	// TODO: step by step implementation of the store, starting with basic claim stuff

}( mediaWiki, wikibase, jQuery ) );

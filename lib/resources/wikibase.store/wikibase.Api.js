/**
 * @file
 * @ingroup Wikibase
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author Tobias Gritschacher
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
 *
 * @param {Number} propertyId
 */
wb.Api = wb.utilities.inherit( PARENT, {

	/**
	 * Set the label of an entity via the API
	 *
	 * @param id the entity id
	 * @param label the label to set
	 * @param language the language in which the label should be set
	 * @returns jQuery.Promise
	 */
	setLabel: function( id, baserevid, label, language ) {
		var params = {
			action: "wbsetlabel",
			id: id,
			value: label,
			language: language
		};
		return this.queryApi( params );
	},

	/**
	 * Set the description of an entity via the API
	 *
	 * @param id the entity id
	 * @param description the description to set
	 * @param language the language in which the description should be set
	 * @returns jQuery.Promise
	 */
	setDescription: function( id, baserevid, description, language ) {
		var params = {
			action: "wbsetdescription",
			id: id,
			value: description,
			language: language
		};
		return this.queryApi( params );
	},

	/**
	 * Set a sitelink for an item via the API
	 *
	 * @param id the entity id
	 * @param site the site of the link
	 * @param title the title to link to
	 * @returns jQuery.Promise
	 */
	setSitelink: function( id, baserevid, site, title ) {
		var params = {
			action: "wbsetsitelink",
			id: id,
			linksite: site,
			linktitle: title
		};
		return this.queryApi( params );
	},

	/**
	 * Revove a sitelink of an item via the API
	 *
	 * @param id the entity id
	 * @param site the site of the link
	 * @returns jQuery.Promise
	 */
	removeSitelink: function( id, baserevid, site ) {
		return this.setSitelink( id, baserevid, site, '' );
	},

	/**
	 * Add and/or remove a number of aliases of an item via the API
	 *
	 * @param id the entity id
	 * @param add String |-seperated list of aliases to add
	 * @param remove String |-seperated list of aliases to remove
	 * @param language the language in which the aliases should be added/removed
	 * @returns
	 */
	setAliases: function( id, baserevid, add, remove, language ) {
		var params = {
			action: "wbsetaliases",
			id: id,
			add: add,
			remove: remove,
			language: language
		};
		return this.queryApi( params );
	},

	/**
	 * submitting the AJAX request to query the API
	 *
	 * @param Object params parameters for API call
	 * @return jQuery.Promise
	 */
	queryApi: function( params ) {
		var api = new mw.Api();
		var deferred = $.Deferred();

		$.extend( params, {
			token: mw.user.tokens.get( 'editToken' )
		} );

		deferred
		.done( function( response ) {
			// TODO: publish
		} )
		.fail( function( textStatus, response ) {
			// TODO: publish
		} );

		api.post( params, {
			ok: function( response ) {
				deferred.resolve( response );
			},
			err: function( textStatus, response ) {
				deferred.reject( textStatus, response );
			}
		} );

		return deferred.promise();
	}
} );

	// TODO: step by step implementation of the store, starting with basic claim stuff

}( mediaWiki, wikibase, jQuery ) );

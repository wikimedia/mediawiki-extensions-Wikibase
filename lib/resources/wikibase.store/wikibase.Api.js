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

wb.Api = $.extend( {

	/**
	 * Sets the label of an entity via the API.
	 *
	 * @param {Number} id entity id
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
		return this.queryApi( params );
	},

	/**
	 * Sets the description of an entity via the API.
	 *
	 * @param {Number} id entity id
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
		return this.queryApi( params );
	},

	/**
	 * Sets a site link for an item via the API.
	 *
	 * @param {Number} id entity id
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
		return this.queryApi( params );
	},

	/**
	 * Removes a sitelink of an item via the API.
	 *
	 * @param {Number} id entity id
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
	 * @param {Number} id entity id
	 * @param {Number} baserevid revision id
	 * @param {String} add |-seperated list of aliases to add
	 * @param {String} remove |-seperated list of aliases to remove
	 * @param {String} language the language in which the aliases should be added/removed
	 * @return {jQuery.Promise}
	 */
	setAliases: function( id, baserevid, add, remove, language ) {
		var params = {
			action: "wbsetaliases",
			id: id,
			add: add,
			remove: remove,
			language: language,
			baserevid: baserevid
		};
		return this.queryApi( params );
	},

	/**
	 * Submits the AJAX request to query the API and triggers resolving the response.
	 *
	 * @param {Object} params parameters for the API call
	 * @return {jQuery.Promise}
	 */
	queryApi: function( params ) {
		var deferred = $.Deferred();

		$.extend( params, { token: mw.user.tokens.get( 'editToken' ) } );

		deferred
		.done( function( response ) {
			// TODO: publish
		} )
		.fail( function( textStatus, response ) {
			// TODO: publish
		} );

		this.post( params, {
			ok: function( response ) {
				deferred.resolve( response );
			},
			err: function( textStatus, response ) {
				deferred.reject( textStatus, response );
			}
		} );

		return deferred.promise();
	}

}, mw.Api.prototype );

/**
 * Constructor to create an API object for interaction with the Wikibase API.
 * This implements the wikibase.EntityStore, so this represents the server sided store which will
 * be accessed via the API.
 * @constructor
 * @extends wb.EntityStore
 * @since 0.2
 *
 * @param {Object} [options] (see mw.Api)
 */
wb.Api = wb.utilities.inherit( PARENT, function( options ) {
	PARENT.apply( this, arguments );
	mw.Api.call( this, options );
}, wb.Api );

	// TODO: step by step implementation of the store, starting with basic claim stuff

}( mediaWiki, wikibase, jQuery ) );

/**
 * @file
 * @ingroup Wikibase
 * @licence GNU GPL v2+
 * @author Daniel Werner
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

} );

	// TODO: step by step implementation of the store, starting with basic claim stuff

}( mediaWiki, wikibase, jQuery ) );

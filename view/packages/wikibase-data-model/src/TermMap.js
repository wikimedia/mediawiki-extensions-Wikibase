( function() {
'use strict';

var PARENT = require( './Map.js' ),
	Term = require( './Term.js' );

/**
 * Map of Term objects.
 * @class TermMap
 * @extends Map
 * @since 1.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {Object} [terms={}]
 */
module.exports = util.inherit( 'WbDataModelTermMap', PARENT, function( terms ) {
	PARENT.call( this, Term, terms );
} );

}() );

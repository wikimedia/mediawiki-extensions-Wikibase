( function() {
'use strict';

var PARENT = require( './Map.js' ),
	MultiTerm = require( './MultiTerm.js' );

/**
 * Map of MultiTerm objects.
 * @class MultiTermMap
 * @extends Map
 * @since 1.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {Object} [multiTerms={}]
 */
module.exports = util.inherit(
	'WbDataModelMultiTermMap',
	PARENT,
	function( multiTerms ) {
		PARENT.call( this, MultiTerm, multiTerms );
	}
);

}() );

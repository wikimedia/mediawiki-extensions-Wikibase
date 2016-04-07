( function( wb ) {
'use strict';

var PARENT = wb.datamodel.Map;

/**
 * Map of MultiTerm objects.
 * @class wikibase.datamodel.MultiTermMap
 * @extends wikibase.datamodel.Map
 * @since 1.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {Object} [multiTerms={}]
 */
wb.datamodel.MultiTermMap = util.inherit(
	'WbDataModelMultiTermMap',
	PARENT,
	function( multiTerms ) {
		PARENT.call( this, wb.datamodel.MultiTerm, multiTerms );
	}
);

}( wikibase ) );

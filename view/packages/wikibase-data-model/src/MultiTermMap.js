/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb ) {
'use strict';

var PARENT = wb.datamodel.Map;

/**
 * @constructor
 * @since 1.0
 *
 * @param {Object} [multiTerms]
 */
wb.datamodel.MultiTermMap = util.inherit(
	'WbDataModelMultiTermMap',
	PARENT,
	function( multiTerms ) {
		PARENT.call( this, wb.datamodel.MultiTerm, multiTerms );
	}
);

}( wikibase ) );

/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb ) {
'use strict';

var PARENT = wb.datamodel.Set;

/**
 * @constructor
 * @since 1.0
 *
 * @param {wikibase.datamodel.MultiTerm[]} [multiTerms]
 */
wb.datamodel.MultiTermSet = util.inherit(
	'WbDataModelMultiTermSet',
	PARENT,
	function( multiTerms ) {
		PARENT.call( this, wb.datamodel.MultiTerm, 'getLanguageCode', multiTerms );
	}
);

}( wikibase ) );

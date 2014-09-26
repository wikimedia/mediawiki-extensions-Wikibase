/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb ) {
'use strict';

var PARENT = wb.datamodel.Set;

/**
 * Unordered set of MultiTerm objects.
 * @constructor
 * @since 0.4
 *
 * @param {wikibase.datamodel.MultiTerm[]} [multiTerms]
 */
wb.datamodel.MultiTermList = util.inherit( 'wbMultiTermList', PARENT, function( multiTerms ) {
	PARENT.call( this, wb.datamodel.MultiTerm, 'getLanguageCode', multiTerms );
} );

}( wikibase ) );

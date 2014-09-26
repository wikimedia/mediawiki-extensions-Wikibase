/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb ) {
'use strict';

var PARENT = wb.datamodel.UnorderedList;

/**
 * Unordered set of Term objects.
 * @constructor
 * @since 0.4
 *
 * @param {wikibase.datamodel.Term[]} [terms]
 */
wb.datamodel.TermList = util.inherit( 'wbTermList', PARENT, function( terms ) {
	PARENT.call( this, wb.datamodel.Term, 'getLanguageCode', terms );
} );

}( wikibase ) );

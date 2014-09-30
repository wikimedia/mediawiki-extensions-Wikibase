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
 * @param {wikibase.datamodel.Term[]} [terms]
 */
wb.datamodel.TermSet = util.inherit( 'WbDataModelTermSet', PARENT, function( terms ) {
	PARENT.call( this, wb.datamodel.Term, 'getLanguageCode', terms );
} );

}( wikibase ) );

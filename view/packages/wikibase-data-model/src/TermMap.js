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
 * @param {Object} [terms]
 */
wb.datamodel.TermMap = util.inherit( 'WbDataModelTermMap', PARENT, function( terms ) {
	PARENT.call( this, wb.datamodel.Term, terms );
} );

}( wikibase ) );

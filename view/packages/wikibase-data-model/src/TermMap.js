( function( wb ) {
'use strict';

var PARENT = wb.datamodel.Map;

/**
 * Map of Term objects.
 * @class wikibase.datamodel.TermMap
 * @extends wikibase.datamodel.Map
 * @since 1.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {Object} [terms={}]
 */
wb.datamodel.TermMap = util.inherit( 'WbDataModelTermMap', PARENT, function( terms ) {
	PARENT.call( this, wb.datamodel.Term, terms );
} );

}( wikibase ) );

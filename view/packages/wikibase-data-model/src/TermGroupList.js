/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb ) {
'use strict';

var PARENT = wb.datamodel.Set;

/**
 * Unordered set of TermGroup objects.
 * @constructor
 * @since 0.4
 *
 * @param {wikibase.datamodel.TermGroup[]} [termGroups]
 */
wb.datamodel.TermGroupList = util.inherit( 'wbTermGroupList', PARENT, function( termGroups ) {
	PARENT.call( this, wb.datamodel.TermGroup, 'getLanguageCode', termGroups );
} );

}( wikibase ) );

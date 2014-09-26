/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb ) {
'use strict';

var PARENT = wb.datamodel.UnorderedList;

/**
 * Unordered set of ClaimGroup objects.
 * @constructor
 * @since 0.4
 *
 * @param {wikibase.datamodel.ClaimGroup[]} [claimGroups]
 */
wb.datamodel.ClaimGroupList = util.inherit( 'wbClaimGroupList', PARENT, function( claimGroups ) {
	PARENT.call( this, wb.datamodel.ClaimGroup, 'getPropertyId', claimGroups );
} );

}( wikibase ) );

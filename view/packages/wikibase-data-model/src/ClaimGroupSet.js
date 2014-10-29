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
 * @param {wikibase.datamodel.ClaimGroup[]} [claimGroups]
 */
wb.datamodel.ClaimGroupSet = util.inherit( 'WbDataModelClaimGroupSet',
	PARENT,
	function( claimGroups ) {
		PARENT.call( this, wb.datamodel.ClaimGroup, 'getKey', claimGroups );
	}
);

}( wikibase ) );

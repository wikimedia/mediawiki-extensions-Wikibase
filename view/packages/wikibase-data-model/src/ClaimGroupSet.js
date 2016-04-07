( function( wb ) {
'use strict';

var PARENT = wb.datamodel.Set;

/**
 * Set of ClaimGroup objects.
 * @class wikibase.datamodel.ClaimGroupSet
 * @extends wikibase.datamodel.Set
 * @since 1.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {wikibase.datamodel.ClaimGroup[]} [claimGroups=new wikibase.datamodel.ClaimGroup]
 */
wb.datamodel.ClaimGroupSet = util.inherit( 'WbDataModelClaimGroupSet',
	PARENT,
	function( claimGroups ) {
		PARENT.call( this, wb.datamodel.ClaimGroup, 'getKey', claimGroups );
	}
);

}( wikibase ) );

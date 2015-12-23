( function( wb ) {
'use strict';

var PARENT = wb.datamodel.Group;

/**
 * List of Claim objects, constrained to a single property id.
 * @class wikibase.datamodel.ClaimGroup
 * @extends wikibase.datamodel.Group
 * @since 1.0
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {string} propertyId
 * @param {wikibase.datamodel.ClaimList} [claimList=new wikibase.datamodel.ClaimList()]
 */
wb.datamodel.ClaimGroup = util.inherit(
	'wbClaimGroup',
	PARENT,
	function WbDataModelClaimGroup( propertyId, claimList ) {
		PARENT.call( this, propertyId, wb.datamodel.ClaimList, 'getPropertyIds', claimList );
	}
);

}( wikibase ) );

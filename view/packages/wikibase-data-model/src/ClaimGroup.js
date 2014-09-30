/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb ) {
'use strict';

var PARENT = wb.datamodel.Group;

/**
 * Ordered list of Claim objects, each featuring the same property id.
 * @constructor
 * @since 1.0
 *
 * @param {wikibase.datamodel.ClaimList} [claimList]
 */
wb.datamodel.ClaimGroup = util.inherit( 'wbClaimGroup', PARENT, function( propertyId, claimList ) {
	PARENT.call( this, propertyId, wb.datamodel.ClaimList, 'getPropertyIds', claimList );
} );

}( wikibase ) );

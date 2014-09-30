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
 * @param {wikibase.datamodel.SiteLink[]} [siteLinks]
 */
wb.datamodel.SiteLinkSet = util.inherit( 'WbDataModelSiteLinkSet', PARENT, function( siteLinks ) {
	PARENT.call( this, wb.datamodel.SiteLink, 'getSiteId', siteLinks );
} );

}( wikibase ) );

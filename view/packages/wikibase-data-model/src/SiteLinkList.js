/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb ) {
'use strict';

var PARENT = wb.datamodel.Set;

/**
 * Unordered set of SiteLink objects.
 * @constructor
 * @since 0.4
 *
 * @param {wikibase.datamodel.SiteLink[]} [siteLinks]
 */
wb.datamodel.SiteLinkList = util.inherit( 'wbSiteLinkList', PARENT, function( siteLinks ) {
	PARENT.call( this, wb.datamodel.SiteLink, 'getSiteId', siteLinks );
} );

}( wikibase ) );

( function( wb ) {
'use strict';

var PARENT = wb.datamodel.Set;

/**
 * Set of SiteLink objects.
 * @class wikibase.datamodel.SiteLinkSet
 * @extends wikibase.datamodel.Set
 * @since 1.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {wikibase.datamodel.SiteLink[]} [siteLinks=[]]
 */
wb.datamodel.SiteLinkSet = util.inherit( 'WbDataModelSiteLinkSet', PARENT, function( siteLinks ) {
	PARENT.call( this, wb.datamodel.SiteLink, 'getSiteId', siteLinks );
} );

}( wikibase ) );

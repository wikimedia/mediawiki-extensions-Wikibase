( function() {
'use strict';

var PARENT = require( './Set.js' ),
	SiteLink = require( './SiteLink.js' );

/**
 * Set of SiteLink objects.
 * @class SiteLinkSet
 * @extends Set
 * @since 1.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {SiteLink[]} [siteLinks=[]]
 */
module.exports = util.inherit( 'WbDataModelSiteLinkSet', PARENT, function( siteLinks ) {
	PARENT.call( this, SiteLink, 'getSiteId', siteLinks );
} );

}() );

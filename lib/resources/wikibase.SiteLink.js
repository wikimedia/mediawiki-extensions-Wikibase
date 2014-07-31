/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, util ) {
'use strict';

/**
 * Represents a site link.
 * @constructor
 * @extends wikibase.datamodel.SiteLink
 * @since 0.5
 */
wb.SiteLink = util.inherit( wb.datamodel.SiteLink, {
	/**
	 * @see wikibase.datamodel.SiteLink._urlEncode
	 *
	 * @param {string} string
	 * @return {string}
	 */
	_urlEncode: function( string ) {
		return mw.util.wikiUrlencode( string );
	}
} );
}( mediaWiki, wikibase, util ) );

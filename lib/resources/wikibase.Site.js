/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $, util ) {
'use strict';

/**
 * Offers information about a site known to the local Wikibase installation.
 * @constructor
 * @extends wikibase.datamodel.Site
 * @since 0.1
 */
wb.Site = util.inherit( wb.datamodel.Site, {
	/**
	 * @return {string}
	 */
	getLanguageDirection: function() {
		var dir = 'ltr',
			languageCode = this.getLanguageCode();

		// language might not be defined in ULS
		if ( wb.getLanguages()[languageCode] ) {
			if ( $.uls.data.isRtl( languageCode ) ) {
				dir = 'rtl';
			}
		} else {
			// TODO: This should probably be logged somehow, because it really shouldn't happen.
			dir = 'auto';
		}

		return dir;
	}
} );

}( wikibase, jQuery, util ) );

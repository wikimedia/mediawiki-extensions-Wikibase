/**
 * Entrypoint for MediaWiki "DataValues" extension JavaScript code. Adds an extension object to the
 * global MediaWiki object and does configuration on "DataValues"  or its dependencies.
 *
 * @file
 * @ingroup ValueView
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */

mediaWiki.ext = mediaWiki.ext || {};

/**
 * Object representing the MeidaWiki "DataValues" extension.
 *
 * @since 0.1
 */
mediaWiki.ext.dataValues = ( function( mw, dataValues, time ) {
	'use strict';

	return new ( function MwExtDataValues() {
		// Make sure time parser works without confusing user (consider user's interface language).
		// Source: http://en.wikipedia.org/wiki/Date_format_by_country
		var userLang = mw.config.get( 'wgUserLanguage' ),
			monthBeforeDayLanguages = [ 'en' ]; // NOTE: add "Palau" if it gets supported by MW

		time.settings.daybeforemonth = $.inArray( userLang, monthBeforeDayLanguages ) === -1;

		// TODO: think of a better way to manage options. This only works because we load the whole
		//  "dataValues.values" module which will result into "time.js" being loaded which results
		//  into the global time object being available. If we would only load "time.js" when it
		//  is required, we could not define its global setting at this entry point!
	} )();

}( mediaWiki, dataValues, time ) );

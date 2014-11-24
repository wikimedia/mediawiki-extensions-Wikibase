/**
 * @ignore
 */
mediaWiki.ext = mediaWiki.ext || {};

/**
 * Object representing the MediaWiki "DataValues" extension.
 * Entrypoint for MediaWiki "DataValues" extension JavaScript code. Adds an extension object to the
 * global MediaWiki object and does configuration on "DataValues"  or its dependencies.
 * @class mediaWiki.ext.dataValues
 * @singleton
 * @since 0.1
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
mediaWiki.ext.dataValues = ( function( mw, dataValues, time, $ ) {
	'use strict';

	/**
	 * Constructor for extension singleton.
	 *
	 * @constructor
	 */
	function MwExtDataValues() {
		time.settings.daybeforemonth = getDayBeforeMonthTimeSettingFromMWContext();
		time.settings.monthnames = getMonthNameTimeSettingsFromMWContext();

		// TODO: think of a better way to manage options. This only works because we load the whole
		//  "dataValues.values" module which will result into "time.js" being loaded which results
		//  into the global time object being available. If we would only load "time.js" when it
		//  is required, we could not define its global setting at this entry point!
	}

	/**
	 * Returns whether the language used in MediaWiki prefers the day before the month in its
	 * numerical date notation.
	 *
	 * @return {boolean}
	 */
	function getDayBeforeMonthTimeSettingFromMWContext() {
		// Make sure time parser works without confusing user (consider user's interface language).
		// Source: http://en.wikipedia.org/wiki/Date_format_by_country
		var userLang = mw.config.get( 'wgUserLanguage' ),
			monthBeforeDayLanguages = [ 'en' ]; // NOTE: add "Palau" if it gets supported by MW

		return $.inArray( userLang, monthBeforeDayLanguages ) === -1;
	}

	/**
	 * Returns an array of arrays where each holds different string representations of a month name.
	 * Considers the MediaWiki user's interface language.
	 *
	 * @return {string[][]}
	 */
	function getMonthNameTimeSettingsFromMWContext() {
		function monthNames( shortNameKey, longNameKey ) {
			var shortName = mw.msg( shortNameKey ),
				longName = mw.msg( longNameKey );

			if( shortName && shortName !== longName ) {
				return [ longName, shortName ];
			}
			return [ longName ];
		}
		return [
			monthNames( 'jan', 'january' ),
			monthNames( 'feb', 'february' ),
			monthNames( 'mar', 'march' ),
			monthNames( 'apr', 'april' ),
			monthNames( 'may', 'may_long' ),
			monthNames( 'jun', 'june' ),
			monthNames( 'jul', 'july' ),
			monthNames( 'aug', 'august' ),
			monthNames( 'sep', 'september' ),
			monthNames( 'oct', 'october' ),
			monthNames( 'nov', 'november' ),
			monthNames( 'dec', 'december' )
		];
	}

	return new MwExtDataValues(); // expose extension singleton

}( mediaWiki, dataValues, time, jQuery ) );

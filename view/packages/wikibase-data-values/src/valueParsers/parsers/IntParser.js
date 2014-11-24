( function( vp, dv, util, $ ) {
	'use strict';

	var PARENT = vp.ValueParser;

	/**
	 * Constructor for string-to-float parsers.
	 * @licence GNU GPL v2+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 * @extends valueParsers.ValueParser
	 * @since 0.1
	 */
	vp.IntParser = util.inherit( PARENT, {
		/**
		 * @see valueParsers.ValueParser.parse
		 * @since 0.1
		 *
		 * @param {string} rawValue
		 * @return jQuery.Promise
		 */
		parse: function( rawValue ) {
			var deferred = $.Deferred();

			// TODO: Localization, option to set integer base
			if( /^(-)?\d+$/.test( rawValue ) ) {
				deferred.resolve( new dv.NumberValue( parseInt( rawValue, 10 ) ) );
			}

			deferred.reject( 'Unable to parse "' + rawValue + '"' );

			return deferred.promise();
		}
	} );

}( valueParsers, dataValues, util, jQuery ) );

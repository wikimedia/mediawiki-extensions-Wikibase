( function( vp, dv, util, $ ) {
	'use strict';

	var PARENT = vp.ValueParser;

	/**
	 * Constructor for string-to-float parsers.
	 * @class valueParsers.IntParser
	 * @extends valueParsers.ValueParser
	 * @since 0.1
	 * @licence GNU GPL v2+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 */
	vp.IntParser = util.inherit( PARENT, {
		/**
		 * @inheritdoc
		 *
		 * @param {string} rawValue
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

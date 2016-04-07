( function( vp, dv, util, $ ) {
	'use strict';

var PARENT = vp.ValueParser;

/**
 * Constructor for string-to-float parsers.
 * @class valueParsers.FloatParser
 * @extends valueParsers.ValueParser
 * @since 0.1
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
vp.FloatParser = util.inherit( PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @param {string} rawValue
	 */
	parse: function( rawValue ) {
		var deferred = $.Deferred();

		// TODO: Localization
		if( !isNaN( parseFloat( rawValue ) ) && isFinite( rawValue ) ) {
			deferred.resolve( new dv.NumberValue( parseFloat( rawValue ) ) );
		}

		deferred.reject( 'Unable to parse "' + rawValue + '"' );

		return deferred.promise();
	}
} );

}( valueParsers, dataValues, util, jQuery ) );

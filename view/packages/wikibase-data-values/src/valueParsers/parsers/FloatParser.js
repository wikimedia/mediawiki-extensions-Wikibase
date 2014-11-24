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
	vp.FloatParser = util.inherit( PARENT, {
		/**
		 * @inheritdoc
		 * @since 0.1
		 *
		 * @param {string} rawValue
		 * @return jQuery.Promise
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

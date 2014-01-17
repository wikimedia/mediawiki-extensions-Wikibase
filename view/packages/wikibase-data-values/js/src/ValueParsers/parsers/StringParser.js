/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < danweetz@web.de >
 */
( function( vp, dv, $, util ) {
	'use strict';

	var PARENT = vp.ValueParser;

	/**
	 * Constructor for string parsers.
	 *
	 * @constructor
	 * @extends vp.ValueParser
	 * @since 0.1
	 */
	vp.StringParser = util.inherit( PARENT, {
		/**
		 * @see vp.ValueParser.parse
		 * @since 0.1
		 *
		 * @param {String} rawValue
		 * @return $.Promise
		 */
		parse: function( rawValue ) {
			var deferred = $.Deferred();

			deferred.resolve( new dv.StringValue( rawValue ) );

			return deferred.promise();
		}
	} );

}( valueParsers, dataValues, jQuery, util ) );

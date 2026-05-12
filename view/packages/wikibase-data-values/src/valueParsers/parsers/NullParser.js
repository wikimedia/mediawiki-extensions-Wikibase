( function( vp, dv, $, util ) {
	'use strict';

var PARENT = vp.ValueParser;

/**
 * Constructor for null parsers.
 * Null parser will take any value for parsing. The parsed value
 * will be an UnknownValue data value except if null got passed in or a DataValue got passed in.
 * In those cases, the value given to the parse function will be the parse result.
 * @class valueParsers.NullParser
 * @extends valueParsers.ValueParser
 * @since 0.1
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 *
 * @constructor
 */
vp.NullParser = util.inherit( PARENT, {
	/**
	 * @inheritdoc
	 */
	parse: function( rawValue ) {
		var deferred = $.Deferred(),
			value = rawValue;

		if( value !== null && !( value instanceof dv.DataValue ) ) {
			value = new dv.UnknownValue( value );
		}

		return deferred.resolve( value ).promise();
	}
} );

}( valueParsers, dataValues, jQuery, util ) );

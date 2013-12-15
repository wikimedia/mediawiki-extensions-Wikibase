/**
 * @file
 * @ingroup ValueParsers
 *
 * @licence GNU GPL v2+
 *
 * @author Daniel Werner < danweetz@web.de >
 */
( function( vp, dv, $, undefined ) {
	'use strict';

	var PARENT = vp.ApiBasedValueParser;

	/**
	 * Constructor for string to number parsers.
	 *
	 * @constructor
	 * @extends vp.ApiBasedValueParser
	 * @since 0.1
	 */
	vp.FloatParser = dv.util.inherit( PARENT, {
		/**
		 * @see ApiBasedValueParser.API_VALUE_PARSER_ID
		 */
		API_VALUE_PARSER_ID: 'float'
	} );

}( valueParsers, dataValues, jQuery ) );

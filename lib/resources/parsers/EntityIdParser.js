/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
( function( wb, vp, $, undefined ) {
	'use strict';

	var PARENT = vp.ApiBasedValueParser;

	/**
	 * Constructor for string to boolean parsers.
	 *
	 * @constructor
	 * @extends vp.ApiBasedValueParser
	 * @since 0.4
	 */
	wb.EntityIdParser = vp.util.inherit( PARENT, {
		/**
		 * @see ApiBasedValueParser.API_VALUE_PARSER_ID
		 */
		API_VALUE_PARSER_ID: 'wikibase-entityid'
	} );

}( wikibase, valueParsers, jQuery ) );

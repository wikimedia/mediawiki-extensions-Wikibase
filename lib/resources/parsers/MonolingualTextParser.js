/**
 * @licence GNU GPL v2+
 */
( function( wb, util ) {
	'use strict';

	var PARENT = wb.parsers.ApiBasedValueParser;

	/**
	 * Constructor for monolingualtext parsers.
	 * @constructor
	 * @extends wikibase.parsers.ApiBasedValueParser
	 * @since 0.5
	 */
	wb.MonolingualTextParser = util.inherit( PARENT, {
		/**
		 * @see wikibase.parsers.ApiBasedValueParser.API_VALUE_PARSER_ID
		 */
		API_VALUE_PARSER_ID: 'monolingualtext'
	} );

}( wikibase, util ) );

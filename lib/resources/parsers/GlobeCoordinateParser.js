/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

	var PARENT = wb.parsers.ApiBasedValueParser;

	/**
	 * Constructor for globe coordinate parsers.
	 * @constructor
	 * @extends wikibase.parsers.ApiBasedValueParser
	 * @since 0.5
	 */
	wb.GlobeCoordinateParser = util.inherit( PARENT, {
		/**
		 * @see wikibase.parsers.ApiBasedValueParser.API_VALUE_PARSER_ID
		 */
		API_VALUE_PARSER_ID: 'globecoordinate'
	} );

}( wikibase, util ) );

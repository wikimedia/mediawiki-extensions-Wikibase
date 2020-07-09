/**
 * @license GPL-2.0-or-later
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */
( function ( vp, dv ) {
	'use strict';

	var PARENT = vp.ValueParser;

	/**
	 * Returns a constructor for a ValueParser which parses using the given wb.api.ParseValueCaller.
	 *
	 * This is necessary since valueParser.ValueParserStore returns a constructor, not an instance, and
	 * we have to pass in the RepoApi wrapped in a wikibase.api.ParseValueCaller.
	 *
	 * @param {wikibase.api.ParseValueCaller} apiValueParser
	 * @return {Function}
	 */
	module.exports = function ( apiValueParser ) {
		/**
		 * Base constructor for objects representing a value parser which is doing an API request to the
		 * 'parseValue' API module.
		 *
		 * @constructor
		 * @extends valueParsers.ValueParser
		 */
		return util.inherit( 'WbApiBasedValueParser', PARENT, {
			/**
			 * The key of the related API parser.
			 *
			 * @type {string}
			 */
			API_VALUE_PARSER_ID: null,

			/**
			 * @see valueParsers.ValueParser.parse
			 *
			 * @param {string} rawValue
			 * @return {Object} jQuery Promise
			 *         Resolved parameters:
			 *         - {dataValues.DataValues}
			 *         Rejected parameters:
			 *         - {string} HTML error message.
			 */
			parse: function ( rawValue ) {
				var deferred = $.Deferred();

				apiValueParser.parseValues( this.API_VALUE_PARSER_ID, [ rawValue ], this._options )
					.done( function ( results ) {
						var result;

						if ( results.length === 0 ) {
							deferred.reject( 'Parse API returned an empty result set.' );
							return;
						}

						try {
							result = dv.newDataValue( results[ 0 ].type, results[ 0 ].value );
							deferred.resolve( result );
						} catch ( error ) {
							deferred.reject( error.message );
						}
					} )
					.fail( function ( error ) {
						deferred.reject( error.detailedMessage || error.code );
					} );

				return deferred.promise();
			}

		} );
	};

}( valueParsers, dataValues ) );

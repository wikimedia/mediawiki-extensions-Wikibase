/**
 * @file
 * @ingroup ValueParsers
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
( function( vp, $ ) {
	'use strict';

	/**
	 * Base constructor for objects representing a value parser.
	 *
	 * @constructor
	 * @abstract
	 * @since 0.1
	 *
	 * @param {Object} options
	 */
	var SELF = vp.ValueParser = function VpValueParser( options ) {
		this._options = $.extend( {}, options || {} );
	};

	$.extend( SELF.prototype, {
		/**
		 * Parser options.
		 * Option name: option value.
		 *
		 * @since 0.1
		 */
		_options: {},

		/**
		 * Returns the parser's options as set in the constructor.
		 *
		 * @since 0.1
		 *
		 * @returns Object
		 */
		getOptions: function() {
			return $.extend( {}, this._options );
		},

		/**
		 * Parses a value. Will return a jQuery.Promise which will be resolved if the parsing is
		 * successful or rejected if it fails. There can be various reasons for the parsing to fail,
		 * e.g. the parser is using the API and the API can't be reached. In case of success, the
		 * callbacks will get a dataValues.DataValue object. In case of failure, the callback's
		 * parameter will be an error object of some sort (not implemented yet!).
		 *
		 * TODO: Specify Error object for parser failure. Consider different error scenarios e.g.
		 *       API can not be reached or real parsing issues.
		 * TODO: Think about introducing parser warnings or a status object in done() callbacks.
		 *
		 * @since 0.1
		 *
		 * @param {*} rawValue
		 *
		 * @return $.Promise In the resolved callbacks the first parameter will be the parsed
		 *         DataValue object or null for an empty value.
		 */
		parse: vp.util.abstractMember

	} );

}( valueParsers, jQuery ) );

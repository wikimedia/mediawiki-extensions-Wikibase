/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
( function( vp, $, util ) {
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
		 * @type {Object}
		 */
		_options: {},

		/**
		 * Returns the parser's options as set in the constructor.
		 *
		 * @since 0.1
		 *
		 * @return {Object}
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
		 * @return {jQuery.Promise}
		 *         Resolved parameters:
		 *         - {dataValues.DataValue|null} Parsed DataValue object or "null" if empty.
		 *         Rejected parameters:
		 *         - {string} HTML error message.
		 */
		parse: util.abstractMember

	} );

}( valueParsers, jQuery, util ) );

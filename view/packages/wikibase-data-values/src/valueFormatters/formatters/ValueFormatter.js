( function( $, vf, util ) {
	'use strict';

/**
 * Base constructor for objects representing a value formatter.
 * @class valueFormatters.ValueFormatter
 * @abstract
 * @since 0.1
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {Object} options
 */
var SELF = vf.ValueFormatter = function VpValueFormatter( options ) {
	this._options = $.extend( {}, options || {} );
};

$.extend( SELF.prototype, {
	/**
	 * Formatter options.
	 * @property {Object}
	 * @private
	 */
	_options: null,

	/**
	 * Returns the formatter's options as set in the constructor.
	 *
	 * @return {Object}
	 */
	getOptions: function() {
		return $.extend( {}, this._options );
	},

	/**
	 * Formats a value. Will return a jQuery.Promise which will be resolved if formatting is
	 * successful or rejected if it fails. There are various reasons why formatting could fail, e.g.
	 * the formatter is using the API and the API cannot be reached. In case of success, the
	 * callbacks will be passed a dataValues.DataValue object. In case of failure, the callback's
	 * parameter will be an error object of some sort (not implemented yet!).
	 * @abstract
	 *
	 * @param {dataValues.DataValue} dataValue
	 * @param {string} [dataTypeId]
	 * @param {string} [outputType] The output's preferred MIME type
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {string|null} return.done.formatted Formatted DataValue.
	 * @return {dataValues.DataValue|null} return.done.dataValue DataValue object that has been
	 *         formatted.
	 * @return {Function} return.fail
	 * @return {string} return.fail.message HTML error message.
	 */
	format: util.abstractMember
	// TODO: Specify Error object for formatter failure. Consider different error scenarios e.g.
	//       API can not be reached or real formatting issues.
	// TODO: Think about introducing formatter warnings or a status object in done() callbacks.

} );

}( jQuery, valueFormatters, util ) );

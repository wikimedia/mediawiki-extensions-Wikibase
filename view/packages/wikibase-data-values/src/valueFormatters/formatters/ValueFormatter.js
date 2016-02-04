( function( $, vf, util ) {
	'use strict';

/**
 * Base constructor for objects representing a value formatter.
 * @class valueFormatters.ValueFormatter
 * @abstract
 * @since 0.1
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
var SELF = vf.ValueFormatter = function VpValueFormatter() {
};

/**
 * @class valueFormatters.ValueFormatter
 */
$.extend( SELF.prototype, {

	/**
	 * Formats a value. Will return a jQuery.Promise which will be resolved if formatting is
	 * successful or rejected if it fails. There are various reasons why formatting could fail, e.g.
	 * the formatter is using an API and the API cannot be reached. In case of success, the
	 * callbacks will be passed a dataValues.DataValue object. In case of failure, the callback's
	 * parameter will be an error object of some sort (not implemented yet!).
	 * @abstract
	 *
	 * @param {dataValues.DataValue} dataValue
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

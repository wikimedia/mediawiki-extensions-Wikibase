/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, vf ) {
	'use strict';

	/**
	 * Base constructor for objects representing a value formatter.
	 * @constructor
	 * @abstract
	 * @since 0.1
	 *
	 * @param {Object} options
	 */
	var SELF = vf.ValueFormatter = function VpValueFormatter( options ) {
		this._options = $.extend( {}, options || {} );
	};

	$.extend( SELF.prototype, {
		/**
		 * Formatter options.
		 * @type {Object}
		 */
		_options: null,

		/**
		 * Returns the formatter's options as set in the constructor.
		 * @since 0.1
		 *
		 * @return {Object}
		 */
		getOptions: function() {
			return $.extend( {}, this._options );
		},

		/**
		 * Formats a value. Will return a jQuery.Promise which will be resolved if formatting is
		 * successful or rejected if it fails. There are various reasons why formatting could fail,
		 * e.g. the formatter is using the API and the API cannot be reached. In case of success,
		 * the callbacks will be passed a dataValues.DataValue object. In case of failure, the
		 * callback's parameter will be an error object of some sort (not implemented yet!).
		 *
		 * TODO: Specify Error object for formatter failure. Consider different error scenarios e.g.
		 *       API can not be reached or real formatting issues.
		 * TODO: Think about introducing formatter warnings or a status object in done() callbacks.
		 *
		 * @since 0.1
		 *
		 * @param {dataValues.DataValue} dataValue
		 *
		 * @return {$.Promise}
		 *         Parameters:
		 *         - {string|null} Formatted DataValue.
		 *         - {dataValues.DataValue|null} DataValue object that has been formatted.
		 */
		format: vf.util.abstractMember

	} );

}( jQuery, valueFormatters ) );

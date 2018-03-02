/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( $, wb, vf, util ) {
	'use strict';

	wb.formatters = wb.formatters || {};

	var PARENT = vf.ValueFormatter;

	/**
	 * A ValueFormatter which is doing an API request to
	 * the FormatSnakValue API module for formatting a value.
	 *
	 * @constructor
	 * @extends valueFormatters.ValueFormatter
	 *
	 * @param {wikibase.api.FormatValueCaller} formatValueCaller
	 * @param {Object} additionalOptions
	 * @param {string|null} dataTypeId
	 * @param {string|null} propertyId
	 * @param {string} outputType
	 */
	wb.formatters.ApiValueFormatter = util.inherit(
		'WbApiValueFormatter',
		PARENT,
		function ( formatValueCaller, additionalOptions, dataTypeId, propertyId, outputType ) {
			this._formatValueCaller = formatValueCaller;
			this._options = additionalOptions;
			this._dataTypeId = dataTypeId;
			this._propertyId = propertyId;
			this._outputType = outputType;
		},
		{
			/**
			 * @var {wikibase.api.FormatValueCaller}
			 */
			_formatValueCaller: null,

			/**
			 * @var {string|null}
			 */
			_dataTypeId: null,

			/**
			 * @var {string|null}
			 */
			_propertyId: null,

			/**
			 * @var {Object}
			 */
			_options: null,

			/**
			 * @var {string} outputType
			 */
			_outputType: null,

			/**
			 * @see valueFormatters.ValueFormatter.format
			 *
			 * @param {dataValues.DataValue} dataValue
			 * @return {jQuery.Promise}
			 *         Resolved parameters:
			 *         - {string} Formatted DataValue.
			 *         - {dataValues.DataValues} Original DataValue object.
			 *         Rejected parameters:
			 *         - {string} HTML error message.
			 */
			format: function ( dataValue ) {
				var deferred = $.Deferred();

				this._formatValueCaller.formatValue(
					dataValue, this._dataTypeId, this._outputType, this._propertyId, this._options
				)
				.done( function ( formattedValue ) {
					deferred.resolve( formattedValue, dataValue );
				} )
				.fail( function ( error ) {
					deferred.reject( error.detailedMessage || error.code );
				} );

				return deferred.promise();
			}

		}
	);

}( jQuery, wikibase, valueFormatters, util ) );

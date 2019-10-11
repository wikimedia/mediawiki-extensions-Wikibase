( function ( wb ) {
	'use strict';

	var MODULE = wb.api;

	/**
	 * @class wikibase.api.FormatValueCaller
	 * @since 1.0
	 * @license GPL-2.0+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 *
	 * @param {wikibase.api.RepoApi} api
	 * @param {dataTypes.DataTypeStore} dataTypeStore
	 */
	var SELF = MODULE.FormatValueCaller = function WbApiFormatValueCaller( api, dataTypeStore ) {
		this._api = api;
		this._dataTypeStore = dataTypeStore;
	};

	$.extend( SELF.prototype, {

		/**
		 * @property {wikibase.api.RepoApi}
		 * @private
		 */
		_api: null,

		/**
		 * @property {dataTypes.DataTypeStore}
		 * @private
		 */
		_dataTypeStore: null,

		/**
		 * Makes a request to the API to format values on the server side. Will return a
		 * `jQuery.Promise` which will be resolved if formatting is successful or rejected if it
		 * fails or the API cannot be reached.
		 *
		 * @param {dataValues.DataValue} dataValue
		 * @param {string|Object} [dataType] `DataType` id.
		 *        Assumed to be `outputFormat` if the `dataTypeStore` the `FormatValueCaller` has
		 *        been initialized with, does not contain a data type whose id matches the string
		 *        supplied via this argument.
		 *        Assumed to be `options` if {Object} and no additional arguments are provided.
		 * @param {string|Object} [outputFormat]
		 *        Assumed to be `options` if {Object} and no additional arguments are provided.
		 * @param {string|Object} [propertyId]
		 *        Assumed to be `options` if {Object} and no additional arguments are provided.
		 * @param {Object} [options]
		 * @return {Object} jQuery.Promise
		 * @return {Function} return.done
		 * @return {string} return.done.formattedValue Formatted `DataValue`.
		 * @return {Function} return.fail
		 * @return {wikibase.api.RepoApiError} error
		 */
		formatValue: function ( dataValue, dataType, outputFormat, propertyId, options ) {

			// Evaluate optional arguments:
			if ( outputFormat === undefined ) {
				if ( $.isPlainObject( dataType ) ) {
					options = dataType;
					dataType = undefined;
				} else if ( !this._dataTypeStore.hasDataType( dataType ) ) {
					outputFormat = dataType;
					dataType = undefined;
				}
			} else if ( propertyId === undefined ) {
				if ( $.isPlainObject( outputFormat ) ) {
					options = outputFormat;
					outputFormat = undefined;
				}
			} else if ( options === undefined ) {
				if ( $.isPlainObject( propertyId ) ) {
					options = propertyId;
					propertyId = undefined;
				}
			}

			var deferred = $.Deferred();

			this._api.formatValue(
				{
					value: dataValue.toJSON(),
					type: dataValue.getType()
				},
				options,
				dataType,
				outputFormat,
				propertyId
			).done( function ( apiResult ) {
				if ( apiResult.result ) {
					deferred.resolve( apiResult.result );
				} else {
					deferred.reject( new wb.api.RepoApiError(
						'unexpected-result',
						'The formatter API returned an unexpected result'
					) );
				}
			} ).fail( function ( errorCode, error ) {
				deferred.reject( wb.api.RepoApiError.newFromApiResponse( error ) );
			} );

			return deferred.promise();
		}

	} );

}( wikibase ) );

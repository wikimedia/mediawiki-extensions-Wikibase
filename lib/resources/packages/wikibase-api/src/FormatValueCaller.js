/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
	'use strict';

	var MODULE = wb.api;

	/**
	 * @constructor
	 *
	 * @param {wikibase.api.RepoApi} api
	 * @param {dataTypes.DataTypeStore} dataTypeStore
	 */
	var SELF = MODULE.FormatValueCaller = function( api, dataTypeStore ) {
		this._api = api;
		this._dataTypeStore = dataTypeStore;
	};

	$.extend( SELF.prototype, {

		/**
		 * @type {wikibase.api.RepoApi}
		 */
		_api: null,

		/**
		 * @type {dataTypes.DataTypeStore}
		 */
		_dataTypeStore: null,

		/**
		 * Makes a request to the API to format values on the server side. Will return a
		 * jQuery.Promise which will be resolved if formatting is successful or rejected if it fails
		 * or the API cannot be reached.
		 * @since 0.5
		 *
		 * @param {dataValues.DataValue} dataValue
		 * @param {string} [dataType]
		 * @param {string} [outputFormat]
		 * @param {Object} [options]
		 * @return {jQuery.Promise}
		 *         Resolved parameters:
		 *         - {string} Formatted DataValue.
		 *         Rejected parameters:
		 *         - {wikibase.api.RepoApiError}
		 */
		formatValue: function( dataValue, dataType, outputFormat, options ) {

			// Evaluate optional arguments:
			if( outputFormat === undefined ) {
				if( $.isPlainObject( dataType ) ) {
					options = dataType;
					dataType = undefined;
				} else if( !this._dataTypeStore.hasDataType( dataType ) ) {
					outputFormat = dataType;
					dataType = undefined;
				}
			} else if( options === undefined ) {
				if( $.isPlainObject( outputFormat ) ) {
					options = outputFormat;
					outputFormat = undefined;
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
				outputFormat
			).done( function( apiResult ) {
				if( apiResult.result ) {
					deferred.resolve( apiResult.result );
				} else {
					deferred.reject( new wb.api.RepoApiError(
						'unexpected-result',
						'The formatter API returned an unexpected result'
					) );
				}
			} ).fail( function( code, details ) {
				deferred.reject( wb.api.RepoApiError.newFromApiResponse( code, details ) );
			} );

			return deferred.promise();
		}

	} );

}( wikibase, jQuery ) );

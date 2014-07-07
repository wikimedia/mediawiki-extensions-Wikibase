/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
	'use strict';

	var SELF = wb.api.FormatValueCaller = function( api, dataTypeStore ) {
		this._api = api;
		this._dataTypeStore = dataTypeStore;
	};

	$.extend( SELF.prototype, {

		/**
		 * @type {wb.RepoApi}
		 */
		_api: null,

		/**
		 * @type {dataTypes.DataTypeStore}
		 */
		_dataTypeStore: null,

		/**
		 * Makes a request to the API to format values on the server side. Will return a jQuery.Promise
		 * which will be resolved if formatting is successful or rejected if it fails or the API cannot
		 * be reached.
		 * @since 0.5
		 *
		 * @param {dataValues.DataValue} dataValue
		 * @param {string} [dataType]
		 * @param {string} [outputFormat]
		 * @param {Object} [options]
		 * @return {$.Promise}
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

			var deferred = $.Deferred(),
				params = {
					action: 'wbformatvalue',
					datavalue:  JSON.stringify( {
						value: dataValue.toJSON(),
						type: dataValue.getType()
					} ),
					options: JSON.stringify( options || {} )
				};

			if( dataType ) {
				params.datatype = dataType;
			}

			if( outputFormat ) {
				params.generate = outputFormat;
			}

			this._api.get( params ).done( function( apiResult ) {
				if( apiResult.result ) {
					deferred.resolve( apiResult.result );
				} else {
					deferred.reject(
						'unexpected-result',
						'The formatter API returned an unexpected result'
					);
				}
			} ).fail( function( code, details ) {
				deferred.reject( code, details );
			} );

			return deferred.promise();
		}

	} );

}( wikibase, jQuery ) );

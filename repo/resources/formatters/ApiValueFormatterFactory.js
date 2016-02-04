/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function( $, wb ) {
	'use strict';

	var PARENT = wb.ValueFormatterFactory;
	wb.formatters = wb.formatters || {};

	/**
	 * @param {wikibase.api.FormatValueCaller} apiCaller
	 * @param {string} languageCode
	 */
	wb.formatters.ApiValueFormatterFactory = util.inherit(
		PARENT,
		function( apiCaller, languageCode ) {
			this._apiCaller = apiCaller;
			this._options = { lang: languageCode };
		},
		{
			/**
			 * @type {wikibase.api.FormatValueCaller}
			 */
			_apiCaller: null,

			/**
			 * @type {Object}
			 */
			_options: null,

			/**
			 * Returns a ValueFormatter instance for the given DataType id and output type
			 *
			 * @param {string|null} dataTypeId
			 * @param {string} outputType
			 * @return {valueFormatters.ValueFormatter}
			 */
			getFormatter: function( dataTypeId, outputType ) {
				var options = this._options;
				if ( dataTypeId === 'quantity' && outputType === 'text/plain' ) {
					options = $.extend( { applyRounding: false, applyUnit: false }, options );
				}
				return new wb.formatters.ApiValueFormatter( this._apiCaller, options, dataTypeId, outputType );
			}
		}
	);

}( jQuery, wikibase ) );

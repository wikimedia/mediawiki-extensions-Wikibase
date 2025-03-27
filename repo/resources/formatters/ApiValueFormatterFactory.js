/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb ) {
	'use strict';

	var PARENT = require( '../../../view/resources/wikibase/wikibase.ValueFormatterFactory.js' );
	wb.formatters = wb.formatters || {};

	/**
	 * @param {wikibase.api.FormatValueCaller} apiCaller
	 * @param {string} languageCode
	 */
	module.exports = class extends PARENT {
		constructor( apiCaller, languageCode ) {
			super();
			/**
			 * @type {wikibase.api.FormatValueCaller}
			 */
			this._apiCaller = apiCaller;
			/**
			 * @type {Object}
			 */
			this._options = { lang: languageCode };
		}

		/**
		 * Returns a ValueFormatter instance for the given DataType ID or Property ID and output type.
		 *
		 * @param {string|null} dataTypeId
		 * @param {string|null} propertyId
		 * @param {string} outputType
		 * @return {valueFormatters.ValueFormatter}
		 */
		getFormatter( dataTypeId, propertyId, outputType ) {
			var options = this._options;
			if ( dataTypeId === 'quantity' && outputType === 'text/plain' ) {
				options = Object.assign( { applyRounding: false, applyUnit: false }, options );
			}
			return new wb.formatters.ApiValueFormatter( this._apiCaller, options, dataTypeId, propertyId, outputType );
		}
	};

}( wikibase ) );

/**
 * @licence GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */
( function( wb, $ ) {
	'use strict';

	/**
	 * @constructor
	 *
	 * @param {$.valueview.ExpertStore} expertStore
	 * @param {valueFormatters.ValueFormatterStore} formatterStore
	 * @param {valueParsers.ValueParserStore} parserStore
	 * @param {string} language
	 * @param {util.MessageProvider} messageProvider
	 */
	var SELF = wb.ValueViewBuilder = function(
		expertStore, formatterStore, parserStore, language, messageProvider
	) {
		this._baseOptions = {
			expertStore: expertStore,
			formatterStore: formatterStore,
			parserStore: parserStore,
			language: language,
			messageProvider: messageProvider
		};
	};

	$.extend( SELF.prototype, {
		/**
		 * @type {Object}
		 */
		_baseOptions: null,

		/**
		 * @param {jQuery} $valueViewDom
		 * @param {dataTypes.DataType} dataType
		 * @param {dataValues.DataValue} dataValue
		 *
		 * @return {jQuery.valueview}
		 */
		initValueView: function( $valueViewDom, dataType, dataValue ) {
			var valueView,
				valueViewOptions = this._getOptions( dataType, dataValue );

			// TODO: Use something like an 'editview' and just change its data type rather than
			// initializing this over and over again and doing the checks.
			$valueViewDom.valueview( valueViewOptions );
			valueView = $valueViewDom.data( 'valueview' );

			return valueView;
		},

		_getOptions: function( dataType, dataValue ) {
			var valueViewOptions = $.extend( {}, this._baseOptions, {
				value: dataValue
			} );

			if( dataType ) {
				valueViewOptions.dataTypeId    = dataType.getId();
				valueViewOptions.dataValueType = dataType.getDataValueType();
			} else if( dataValue ) {
				valueViewOptions.dataValueType = dataValue.getType();
			}

			return valueViewOptions;
		}
	} );

}( wikibase, jQuery ) );

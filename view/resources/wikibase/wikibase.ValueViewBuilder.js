/**
 * @licence GNU GPL v2+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function( wb, $ ) {
	'use strict';

	/**
	 * @constructor
	 *
	 * @param {jQuery.valueview.ExpertStore} expertStore
	 * @param {valueFormatters.ValueFormatterStore} formatterStore
	 * @param {valueParsers.ValueParserStore} parserStore
	 * @param {string} language
	 * @param {util.MessageProvider} messageProvider
	 * @param {util.ContentLanguages} contentLanguages
	 * @param {string|null} [vocabularyLookupApiUrl=null]
	 */
	var SELF = wb.ValueViewBuilder = function WbValueViewBuilder(
		expertStore,
		formatterStore,
		parserStore,
		language,
		messageProvider,
		contentLanguages,
		vocabularyLookupApiUrl
	) {
		this._baseOptions = {
			expertStore: expertStore,
			formatterStore: formatterStore,
			parserStore: parserStore,
			language: language,
			messageProvider: messageProvider,
			contentLanguages: contentLanguages,
			vocabularyLookupApiUrl: vocabularyLookupApiUrl || null
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

			if( !dataType || ( dataValue && dataValue.getType() === 'undeserializable' ) ) {
				// FIXME: For now, treat value with unknown data type (e.g. the property is
				// deleted) in same way as undeserializable and not allow it to be editable.
				// If we allow it to be edited, it might be something like commons media but
				// when treated as a string (based on value type only), then it might be
				// edited in a way that it becomes an invalid commons media value. Then
				// the property is undeleted and we have unexpected behavior.
				valueViewOptions.dataValueType = 'undeserializable';
			} else {
				valueViewOptions.dataTypeId = dataType.getId();
				valueViewOptions.dataValueType = dataType.getDataValueType();
			}

			return valueViewOptions;
		}
	} );

}( wikibase, jQuery ) );

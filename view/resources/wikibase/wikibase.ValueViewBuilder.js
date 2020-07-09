/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function () {
	'use strict';

	/**
	 * @constructor
	 *
	 * @param {jQuery.valueview.ExpertStore} expertStore
	 * @param {wikibase.ValueFormatterFactory} formatterFactory
	 * @param {valueParsers.ValueParserStore} parserStore
	 * @param {string} language
	 * @param {util.MessageProvider} messageProvider
	 * @param {util.ContentLanguages} contentLanguages
	 * @param {string|null} [vocabularyLookupApiUrl=null]
	 * @param {string} commonsApiUrl
	 */
	var SELF = function WbValueViewBuilder(
		expertStore,
		formatterFactory,
		parserStore,
		language,
		messageProvider,
		contentLanguages,
		vocabularyLookupApiUrl,
		commonsApiUrl
	) {
		this._baseOptions = {
			expertStore: expertStore,
			parserStore: parserStore,
			language: language,
			messageProvider: messageProvider,
			contentLanguages: contentLanguages,
			vocabularyLookupApiUrl: vocabularyLookupApiUrl || null,
			commonsApiUrl: commonsApiUrl
		};
		this._formatterFactory = formatterFactory;
	};

	$.extend( SELF.prototype, {
		/**
		 * @member {Object}
		 */
		_baseOptions: null,

		/**
		 * @member {wikibase.ValueFormatterFactory}
		 */
		_formatterFactory: null,

		/**
		 * @param {jQuery} $valueViewDom
		 * @param {wikibase.dataTypes.DataType|null} dataType
		 * @param {dataValues.DataValue|null} dataValue
		 * @param {string|null} propertyId
		 *
		 * @return {jQuery.valueview}
		 */
		initValueView: function ( $valueViewDom, dataType, dataValue, propertyId ) {
			var valueView,
				valueViewOptions = this._getOptions( dataType, dataValue, propertyId );

			// TODO: Use something like an 'editview' and just change its data type rather than
			// initializing this over and over again and doing the checks.
			$valueViewDom.valueview( valueViewOptions );
			valueView = $valueViewDom.data( 'valueview' );

			return valueView;
		},

		/**
		 * @param {wikibase.dataTypes.DataType|null} dataType
		 * @param {dataValues.DataValue|null} dataValue
		 * @param {string|null} propertyId
		 *
		 * @return {Object}
		 */
		_getOptions: function ( dataType, dataValue, propertyId ) {
			var dataTypeId = dataType && dataType.getId();
			var valueViewOptions = $.extend( {}, this._baseOptions, {
				htmlFormatter: this._formatterFactory.getFormatter( dataTypeId, propertyId, 'text/html; disposition=verbose-preview' ),
				plaintextFormatter: this._formatterFactory.getFormatter( dataTypeId, propertyId, 'text/plain' ),
				value: dataValue
			} );

			if ( !dataType || ( dataValue && dataValue.getType() === 'undeserializable' ) ) {
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

	module.exports = SELF;
}() );

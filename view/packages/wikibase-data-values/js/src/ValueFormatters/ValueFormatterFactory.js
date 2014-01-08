/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, vf, dt ) {
	'use strict';

	/**
	 * Factory managing ValueFormatter instances
	 * @constructor
	 * @since 0.1
	 *
	 * @param {Function} [DefaultFormatter] Constructor of a default formatter that shall be
	 *        returned when no formatter is registered for a specific purpose.
	 */
	var SELF = vf.ValueFormatterFactory = function VpValueFormatterFactory( DefaultFormatter ) {
		this._DefaultFormatter = DefaultFormatter || null;
		this._formattersForDataTypes = {};
		this._formattersForDataValueTypes = {};
	};

	$.extend( SELF.prototype, {
		/**
		 * Default formatter constructor to be returned when no formatter is registered for a
		 * specific purpose.
		 * @type {Function|null}
		 */
		_DefaultFormatter: null,

		/**
		 * @type {Object}
		 */
		_formattersForDataTypes: null,

		/**
		 * @type {Object}
		 */
		_formattersForDataValueTypes: null,

		/**
		 * Registers a formatter.
		 * @since 0.1
		 *
		 * @param {dataTypes.DataType|string} purpose DataType object or DataValue type.
		 * @param {Function} Formatter ValueFormatter constructor.
		 *
		 * @throws {Error} if the formatter constructor is invalid.
		 * @throws {Error} no proper purpose is provided.
		 */
		registerFormatter: function( purpose, Formatter ) {
			if( !$.isFunction( Formatter ) ) {
				throw new Error( 'Invalid ValueFormatter constructor' );
			}

			if( purpose instanceof dt.DataType ) {
				this._registerDataTypeFormatter( purpose, Formatter );
			} else if( typeof purpose === 'string' ) {
				this._registerDataValueFormatter( purpose, Formatter );
			} else {
				throw new Error( 'No sufficient purpose provided what to register the formatter '
					+ 'for' );
			}
		},

		/**
		 * Registers a formatter for a certain data type.
		 * @since 0.1
		 *
		 * @param {dataTypes.DataType} dataType
		 * @param {Function} Formatter
		 *
		 * @throws {Error} if a formatter for the specified dataType object is registered already.
		 */
		_registerDataTypeFormatter: function( dataType, Formatter ) {
			assertIsValueFormatterConstructor( Formatter );

			if( this._formattersForDataTypes[dataType.getId()] ) {
				throw new Error( 'Formatter for DataType "' + dataType.getId() + '" is registered '
					+ 'already' );
			}

			this._formattersForDataTypes[dataType.getId()] = Formatter;
		},

		/**
		 * Registers a formatter for a certain data value type.
		 * @since 0.1
		 *
		 * @param {string} dataValueType
		 * @param {Function} Formatter
		 *
		 * @throws {Error} if a formatter for the specified DataValue type is registered already.
		 */
		_registerDataValueFormatter: function( dataValueType, Formatter ) {
			assertIsValueFormatterConstructor( Formatter );

			if( this._formattersForDataValueTypes[dataValueType] ) {
				throw new Error( 'Formatter for DataValue type "' + dataValueType + '" is '
					+ 'registered already' );
			}

			this._formattersForDataValueTypes[dataValueType] = Formatter;
		},

		/**
		 * Returns the ValueFormatter constructor registered for the specified purpose or the
		 * default formatter if no ValueFormatter is registered for that purpose.
		 * @since 0.1
		 *
		 * @param {dataTypes.DataType|string} purpose DataType object or DataValue type.
		 * @return {Function|null}
		 *
		 * @throws {Error} if no proper purpose is provided to retrieve a formatter.
		 */
		getFormatter: function( purpose ) {
			var dataValueType,
				dataTypeId,
				formatter;

			if( purpose instanceof dataTypes.DataType ) {
				dataValueType = purpose.getDataValueType();
				dataTypeId = purpose.getId();
			} else if( typeof purpose === 'string' ) {
				dataValueType = purpose;
			} else {
				throw new Error( 'No sufficient purpose provided for choosing a formatter' );
			}

			if( dataTypeId ) {
				formatter = this._formattersForDataTypes[dataTypeId];
			}

			if( !formatter ) {
				// No formatter for specified data type or only DataValue provided.
				formatter = this._formattersForDataValueTypes[dataValueType];
			}

			return formatter || this._DefaultFormatter;
		}
	} );

	/**
	 * @param {Function} Formatter
	 * @throws {Error} if the provided argument is not a valueFormatters.ValueFormatter constructor.
	 */
	function assertIsValueFormatterConstructor( Formatter ) {
		if( !$.isFunction( Formatter ) && Formatter.prototype instanceof vf.ValueFormatter ) {
			throw new Error( 'Invalid ValueFormatter constructor' );
		}
	}

}( jQuery, valueFormatters, dataTypes ) );

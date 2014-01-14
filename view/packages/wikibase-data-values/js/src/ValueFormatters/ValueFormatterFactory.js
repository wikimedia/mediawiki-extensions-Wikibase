/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, vf ) {
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
		 * Registers a formatter for a certain data type.
		 * @since 0.1
		 *
		 * @param {Function} Formatter
		 * @param {string} dataTypeId
		 *
		 * @throws {Error} if a formatter for the specified dataType object is registered already.
		 */
		registerDataTypeFormatter: function( Formatter, dataTypeId ) {
			assertIsValueFormatterConstructor( Formatter );

			if( this._formattersForDataTypes[dataTypeId] ) {
				throw new Error( 'Formatter for DataType "' + dataTypeId + '" is registered '
					+ 'already' );
			}

			this._formattersForDataTypes[dataTypeId] = Formatter;
		},

		/**
		 * Registers a formatter for a certain data value type.
		 * @since 0.1
		 *
		 * @param {Function} Formatter
		 * @param {string} dataValueType
		 *
		 * @throws {Error} if a formatter for the specified DataValue type is registered already.
		 */
		registerDataValueFormatter: function( Formatter, dataValueType ) {
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
		 * @param {string} dataValueType
		 * @param {string} [dataTypeId]
		 * @return {Function|null}
		 *
		 * @throws {Error} if no proper purpose is provided to retrieve a formatter.
		 */
		getFormatter: function( dataValueType, dataTypeId ) {
			var formatter;

			if( typeof dataTypeId === 'string' ) {
				formatter = this._formattersForDataTypes[dataTypeId];
			}

			if( !formatter && typeof dataValueType === 'string' ) {
				formatter = this._formattersForDataValueTypes[dataValueType];
			} else if( !formatter ) {
				throw new Error( 'No sufficient purpose provided for choosing a formatter' );
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

}( jQuery, valueFormatters ) );

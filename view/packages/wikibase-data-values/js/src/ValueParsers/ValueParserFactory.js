/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, vp, dt ) {
	'use strict';

	/**
	 * Factory managing ValueParser instances
	 * @constructor
	 * @since 0.1
	 *
	 * @param {Function} [DefaultParser] Constructor of a default parser that shall be returned when
	 *        no parser is registered for a specific purpose.
	 */
	var SELF = vp.ValueParserFactory = function VpValueParserFactory( DefaultParser ) {
		this._DefaultParser = DefaultParser || null;
		this._parsersForDataTypes = {};
		this._parsersForDataValueTypes = {};
	};

	$.extend( SELF.prototype, {
		/**
		 * Default parser constructor to be returned when no parser is registered for a specific
		 * purpose.
		 * @type {Function|null}
		 */
		_DefaultParser: null,

		/**
		 * @type {Object}
		 */
		_parsersForDataTypes: null,

		/**
		 * @type {Object}
		 */
		_parsersForDataValueTypes: null,

		/**
		 * Registers a parser.
		 * @since 0.1
		 *
		 * @param {dataTypes.DataType|string} purpose DataType object or DataValue type.
		 * @param {Function} Parser ValueParser constructor.
		 *
		 * @throws {Error} if the parser constructor is invalid.
		 * @throws {Error} no proper purpose is provided.
		 */
		registerParser: function( purpose, Parser ) {
			if( !$.isFunction( Parser ) ) {
				throw new Error( 'Invalid ValueParser constructor' );
			}

			if( purpose instanceof dt.DataType ) {
				this._registerDataTypeParser( purpose, Parser );
			} else if( typeof purpose === 'string' ) {
				this._registerDataValueParser( purpose, Parser );
			} else {
				throw new Error( 'No sufficient purpose provided what to register the parser for' );
			}
		},

		/**
		 * Registers a parser for a certain data type.
		 * @since 0.1
		 *
		 * @param {dataTypes.DataType} dataType
		 * @param {Function} Parser
		 *
		 * @throws {Error} if a parser for the specified dataType object is registered already.
		 */
		_registerDataTypeParser: function( dataType, Parser ) {
			assertIsValueParserConstructor( Parser );

			if( this._parsersForDataTypes[dataType.getId()] ) {
				throw new Error( 'Parser for DataType "' + dataType.getId() + '" is registered '
					+ 'already' );
			}

			this._parsersForDataTypes[dataType.getId()] = Parser;
		},

		/**
		 * Registers a parser for a certain data value type.
		 * @since 0.1
		 *
		 * @param {string} dataValueType
		 * @param {Function} Parser
		 *
		 * @throws {Error} if a parser for the specified DataValue type is registered already.
		 */
		_registerDataValueParser: function( dataValueType, Parser ) {
			assertIsValueParserConstructor( Parser );

			if( this._parsersForDataValueTypes[dataValueType] ) {
				throw new Error( 'Parser for DataValue type "' + dataValueType + '" is registered '
					+ 'already' );
			}

			this._parsersForDataValueTypes[dataValueType] = Parser;
		},

		/**
		 * Returns the ValueParser constructor registered for the specified purpose or the default
		 * parser if no ValueParser is registered for that purpose.
		 * @since 0.1
		 *
		 * @param {dataTypes.DataType|string} purpose DataType object or DataValue type.
		 * @return {Function|null}
		 *
		 * @throws {Error} if no proper purpose is provided to retrieve a parser.
		 */
		getParser: function( purpose ) {
			var dataValueType,
				dataTypeId,
				parser;

			if( purpose instanceof dataTypes.DataType ) {
				dataValueType = purpose.getDataValueType();
				dataTypeId = purpose.getId();
			} else if( typeof purpose === 'string' ) {
				dataValueType = purpose;
			} else {
				throw new Error( 'No sufficient purpose provided for choosing a parser' );
			}

			if( dataTypeId ) {
				parser = this._parsersForDataTypes[dataTypeId];
			}

			if( !parser ) {
				// No parser for specified data type or only DataValue provided.
				parser = this._parsersForDataValueTypes[dataValueType];
			}

			return parser || this._DefaultParser;
		}
	} );

	/**
	 * @param {Function} Parser
	 * @throws {Error} if the provided argument is not a valueParsers.ValueParser constructor.
	 */
	function assertIsValueParserConstructor( Parser ) {
		if( !$.isFunction( Parser ) && Parser.prototype instanceof vp.ValueParser ) {
			throw new Error( 'Invalid ValueParser constructor' );
		}
	}

}( jQuery, valueParsers, dataTypes ) );

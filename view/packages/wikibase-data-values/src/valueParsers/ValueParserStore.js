( function( $, vp ) {
	'use strict';

	/**
	 * Store managing ValueParser instances
	 * @licence GNU GPL v2+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 * @since 0.1
	 *
	 * @param {Function} [DefaultParser] Constructor of a default parser that shall be returned when
	 *        no parser is registered for a specific purpose.
	 */
	var SELF = vp.ValueParserStore = function VpValueParserStore( DefaultParser ) {
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
		 * Registers a parser for a certain data type.
		 * @since 0.1
		 *
		 * @param {Function} Parser
		 * @param {string} dataTypeId
		 *
		 * @throws {Error} if no data type id is specified.
		 * @throws {Error} if a parser for the specified dataType object is registered already.
		 */
		registerDataTypeParser: function( Parser, dataTypeId ) {
			assertIsValueParserConstructor( Parser );

			if( dataTypeId === undefined ) {
				throw new Error( 'No proper data type id provided to register the parser for' );
			}

			if( this._parsersForDataTypes[dataTypeId] ) {
				throw new Error( 'Parser for DataType "' + dataTypeId + '" is registered already' );
			}

			this._parsersForDataTypes[dataTypeId] = Parser;
		},

		/**
		 * Registers a parser for a certain data value type.
		 * @since 0.1
		 *
		 * @param {Function} Parser
		 * @param {string} dataValueType
		 *
		 * @throws {Error} if no data value type is specified.
		 * @throws {Error} if a parser for the specified DataValue type is registered already.
		 */
		registerDataValueParser: function( Parser, dataValueType ) {
			assertIsValueParserConstructor( Parser );

			if( dataValueType === undefined ) {
				throw new Error( 'No proper data value type provided to register the parser for' );
			}

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
		 * @param {string} dataValueType
		 * @param {string} [dataTypeId]
		 * @return {Function|null}
		 *
		 * @throws {Error} if no proper purpose is provided to retrieve a parser.
		 */
		getParser: function( dataValueType, dataTypeId ) {
			var parser;

			if( typeof dataTypeId === 'string' ) {
				parser = this._parsersForDataTypes[dataTypeId];
			}

			if( !parser && typeof dataValueType === 'string' ) {
				parser = this._parsersForDataValueTypes[dataValueType];
			} else if( !parser ) {
				throw new Error( 'No sufficient purpose provided for choosing a parser' );
			}

			return parser || this._DefaultParser;
		}
	} );

	/**
	 * @param {Function} Parser
	 * @throws {Error} if the provided argument is not a valueParsers.ValueParser constructor.
	 */
	function assertIsValueParserConstructor( Parser ) {
		if( !( $.isFunction( Parser ) && Parser.prototype instanceof vp.ValueParser ) ) {
			throw new Error( 'Invalid ValueParser constructor' );
		}
	}

}( jQuery, valueParsers ) );

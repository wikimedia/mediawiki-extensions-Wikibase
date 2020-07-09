( function ( wb ) {
	'use strict';

	/**
	 * Base constructor for objects representing a data type.
	 *
	 * @class wikibase.dataTypes.DataType
	 * @abstract
	 * @since 0.1
	 * @license GPL-2.0-or-later
	 * @author Daniel Werner
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 * @param {string} dataTypeId
	 * @param {string} dataValueType
	 *
	 * @throws {Error} if data type id is not provided as a string.
	 * @throws {Error} if data value type is not provided as a string.
	 */
	var SELF = function DtDataType( dataTypeId, dataValueType ) {
		if ( !dataTypeId || typeof dataTypeId !== 'string' ) {
			throw new Error( 'A data type\'s ID has to be a string' );
		}

		if ( typeof dataValueType !== 'string' ) {
			throw new Error( 'A data value type has to be given in form of a string' );
		}

		this._id = dataTypeId;
		this._dataValueType = dataValueType;
	};

	/**
	 * @class wikibase.dataTypes.DataType
	 */
	$.extend( SELF.prototype, {
		/**
		 * Data type (a.k.a. property type) identifier.
		 *
		 * @property {string}
		 * @private
		 */
		_id: null,

		/**
		 * Identifier of the data value type internally used by this data type.
		 *
		 * @property {string}
		 * @private
		 */
		_dataValueType: null,

		/**
		 * Returns the data type (a.k.a. property type) identifier.
		 *
		 * @return {string}
		 */
		getId: function () {
			return this._id;
		},

		/**
		 * Returns the identifier of the data value type internally used by this data type.
		 *
		 * @return {string}
		 */
		getDataValueType: function () {
			return this._dataValueType;
		}
	} );

	/**
	 * Creates a new DataType object from a given JSON structure.
	 *
	 * @static
	 *
	 * @param {string} dataTypeId
	 * @param {Object} json
	 * @return {dataTypes.DataType}
	 */
	SELF.newFromJSON = function ( dataTypeId, json ) {
		return new SELF( dataTypeId, json.dataValueType );
	};

	module.exports = SELF;

}( wikibase ) );

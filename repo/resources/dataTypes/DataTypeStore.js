( function () {
	'use strict';

	var DataType = require( './DataType.js' );

	/**
	 * @since 0.2
	 * @license GPL-2.0-or-later
	 * @author H. Snater < mediawiki@snater.com >
	 * @class dataTypes.DataTypeStore
	 */
	class DataTypeStore {
		constructor() {
			/**
			 * Data type definitions.
			 *
			 * @property {Object} [_dataTypes={}]
			 * @private
			 */
			this._dataTypes = {};
		}

		/**
		 * Returns the data type of a specific data type id.
		 *
		 * @param {string} dataTypeId
		 * @return {dataTypes.DataType|null}
		 *
		 * @throws {Error} when supplied data type id is not a string.
		 */
		getDataType( dataTypeId ) {
			if ( !dataTypeId || typeof dataTypeId !== 'string' ) {
				throw new Error( 'The ID given to identify a data type needs to be a string' );
			}
			return this._dataTypes[ dataTypeId ] || null;
		}

		/**
		 * Returns if there is a DataType of the provided type.
		 *
		 * @param {string} dataTypeId
		 * @return {boolean}
		 */
		hasDataType( dataTypeId ) {
			return this._dataTypes[ dataTypeId ] !== undefined;
		}

		/**
		 * Registers a new data type. A data type already registered for the id of the new data type
		 * will be overwritten.
		 *
		 * @param {dataTypes.DataType} dataType
		 *
		 * @throws {Error} if data type is not a DataType instance.
		 */
		registerDataType( dataType ) {
			if ( !( dataType instanceof DataType ) ) {
				throw new Error( 'Can only register instances of wikibase.dataTypes.DataType' );
			}
			this._dataTypes[ dataType.getId() ] = dataType;
		}
	}

	module.exports = DataTypeStore;

}() );

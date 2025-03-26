( function ( wb ) {
	'use strict';

	/**
	 * Base constructor for objects representing a data type.
	 *
	 * @class wikibase.dataTypes.DataType
	 * @since 0.1
	 * @license GPL-2.0-or-later
	 * @author Daniel Werner
	 * @author H. Snater < mediawiki@snater.com >
	 */
	class DataType {

		/**
		 * @constructor
		 * @param {string} dataTypeId
		 * @param {string} dataValueType
		 *
		 * @throws {Error} if data type id is not provided as a string.
		 * @throws {Error} if data value type is not provided as a string.
		 */
		constructor( dataTypeId, dataValueType ) {
			if ( !dataTypeId || typeof dataTypeId !== 'string' ) {
				throw new Error( 'A data type\'s ID has to be a string' );
			}

			if ( typeof dataValueType !== 'string' ) {
				throw new Error( 'A data value type has to be given in form of a string' );
			}

			/**
			 * Data type (a.k.a. property type) identifier.
			 *
			 * @property {string}
			 * @private
			 */
			this._id = dataTypeId;

			/**
			 * Identifier of the data value type internally used by this data type.
			 *
			 * @property {string}
			 * @private
			 */
			this._dataValueType = dataValueType;
		}

		/**
		 * Returns the data type (a.k.a. property type) identifier.
		 *
		 * @return {string}
		 */
		getId() {
			return this._id;
		}

		/**
		 * Returns the identifier of the data value type internally used by this data type.
		 *
		 * @return {string}
		 */
		getDataValueType() {
			return this._dataValueType;
		}

		/**
		 * Creates a new DataType object from a given JSON structure.
		 *
		 * @static
		 *
		 * @param {string} dataTypeId
		 * @param {Object} json
		 * @return {wikibase.dataTypes.DataType}
		 */
		static newFromJSON( dataTypeId, json ) {
			return new DataType( dataTypeId, json.dataValueType );
		}
	}

	module.exports = DataType;

}( wikibase ) );

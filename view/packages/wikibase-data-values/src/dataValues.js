/**
 * Global 'dataValues' object.
 * @class dataValues
 * @singleton
 * @since 0.1
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
this.dataValues = new ( function Dv() {
	'use strict';

	var dvs = [];

	/**
	 * Returns the constructor associated with the provided DataValue type.
	 * @ignore
	 *
	 * @param {string} dataValueType
	 * @return {dataValues.DataValue}
	 *
	 * @throws {Error} if the data value type is unknown.
	 */
	function getDataValueConstructor( dataValueType ) {
		if ( dvs[dataValueType] !== undefined ) {
			return dvs[dataValueType];
		}

		throw new Error( 'Unknown data value type "' + dataValueType + '" has no associated '
			+ 'DataValue class' );
	}

	/**
	 * Constructs and returns a new DataValue of specified type with the provided data.
	 *
	 * @param {string} dataValueType
	 * @param {*} data
	 * @return {dataValues.DataValue}
	 */
	this.newDataValue = function( dataValueType, data ) {
		return getDataValueConstructor( dataValueType ).newFromJSON( data );
	};

	/**
	 * Returns the types of the registered DataValues.
	 *
	 * @return {string[]}
	 */
	this.getDataValues = function() {
		var keys = [];

		for ( var key in dvs ) {
			if ( dvs.hasOwnProperty( key ) ) {
				keys.push( key );
			}
		}

		return keys;
	};

	/**
	 * Returns if there is a DataValue with the provided type.
	 *
	 * @param {string} dataValueType
	 * @return {boolean}
	 */
	this.hasDataValue = function( dataValueType ) {
		return dvs[dataValueType] !== undefined;
	};

	/**
	 * Registers a data value.
	 * If a data value with the provided name is registered already, the registration will be
	 * overwritten with the newly provided data.
	 *
	 * @param {dataValues.DataValue} dataValueConstructor
	 */
	this.registerDataValue = function( dataValueConstructor ) {
		dvs[dataValueConstructor.TYPE] = dataValueConstructor;
	};

	return this;

} )();

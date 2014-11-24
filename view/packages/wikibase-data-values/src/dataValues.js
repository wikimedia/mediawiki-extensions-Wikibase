/**
 * Global 'dataValues' object.
 * @class dataValues
 * @singleton
 * @since 0.1
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
this.dataValues = new( function Dv() {
	'use strict';

	var dvs = [];

	/**
	 * Returns the constructor associated with the provided DataValue type.
	 *
	 * @since 0.1
	 *
	 * @param {String} dataValueType
	 *
	 * @return dv.DataValue
	 */
	function getDataValueConstructor( dataValueType ) {
		if ( dvs[dataValueType] !== undefined ) {
			return dvs[dataValueType];
		}

		throw new Error( 'Unknown data value type "' + dataValueType + '" has no associated DataValue class' );
	}

	/**
	 * Constructs and returns a new DataValue of specified type with the provided data.
	 *
	 * @since 0.1
	 *
	 * @throws {Error} If the a unknown data value type is given.
	 * @throws {Error} If the given data is not sufficient for constructing the data value.
	 *
	 * @param {String} dataValueType
	 * @param {*} data
	 * @return dv.DataValue
	 */
	this.newDataValue = function( dataValueType, data ) {
		return getDataValueConstructor( dataValueType ).newFromJSON( data );
	};

	/**
	 * Returns the types of the registered DataValues.
	 *
	 * @since 0.1
	 *
	 * @return String[]
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
	 * @since 0.1
	 *
	 * @param {String} dataValueType
	 *
	 * @return Boolean
	 */
	this.hasDataValue = function( dataValueType ) {
		return dvs[dataValueType] !== undefined;
	};

	/**
	 * Registers a data value.
	 * If there is a data value already with the provided name,
	 * it will be overridden with the newly provided data.
	 *
	 * @since 0.1
	 *
	 * @param {dv.DataValue} dataValueConstructor
	 */
	this.registerDataValue = function( dataValueConstructor ) {
		dvs[dataValueConstructor.TYPE] = dataValueConstructor;
	};

	return this;

} )();

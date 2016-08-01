( function( dv, util ) {
	'use strict';

	var PARENT = dv.DataValue;

/**
 * Constructor for a data value representing a quantity.
 * @class dataValues.QuantityValue
 * @extends dataValues.DataValue
 * @since 0.1
 * @license GPL-2.0+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {dataValues.DecimalValue} amount Numeric string or a number.
 * @param {string} unit A unit identifier. Must not be empty, use "1" for unit-less quantities.
 * @param {dataValues.DecimalValue|null} [upperBound] The upper bound of the quantity, inclusive.
 * @param {dataValues.DecimalValue|null} [lowerBound] The lower bound of the quantity, inclusive.
 *
 * @throws {Error} if constructor parameters are invalid.
 */
var SELF
	= dv.QuantityValue
	= util.inherit( 'DvQuantityValue', PARENT, function( amount, unit, upperBound, lowerBound ) {
		if ( !amount || !( amount instanceof dv.DecimalValue ) ) {
			throw new Error( 'amount needs to be a DecimalValue object' );
		}

		if ( typeof unit !== 'string' || unit === '' ) {
			throw new Error( 'unit must be a non-empty string (use "1" for unit-less quantities)' );
		}

		// Both can be null/undefined. But if one is set, both must be set.
		if ( upperBound || lowerBound ) {
			if ( !( upperBound instanceof dv.DecimalValue )
				|| !( lowerBound instanceof dv.DecimalValue )
			) {
				throw new Error( 'upperBound and lowerBound must both be defined or both undefined' );
			}
		}

		this._amount = amount;
		this._unit = unit;
		this._lowerBound = lowerBound || null;
		this._upperBound = upperBound || null;
	},
{
	/**
	 * @property {dataValues.DecimalValue}
	 * @private
	 */
	_amount: null,

	/**
	 * @property {string}
	 * @private
	 */
	_unit: null,

	/**
	 * @property {dataValues.DecimalValue|null}
	 * @private
	 */
	_lowerBound: null,

	/**
	 * @property {dataValues.DecimalValue|null}
	 * @private
	 */
	_upperBound: null,

	/**
	 * @inheritdoc
	 *
	 * @return {string}
	 */
	getSortKey: function() {
		return this._amount.getValue();
	},

	/**
	 * @inheritdoc
	 *
	 * @return {dataValues.QuantityValue}
	 */
	getValue: function() {
		return this;
	},

	/**
	 * Returns the amount held by this quantity, as a string in standard format.
	 *
	 * @return {string}
	 */
	getAmount: function() {
		return this._amount;
	},

	/**
	 * Returns the unit held by this quantity. Returns null in case of unit-less quantities.
	 *
	 * @return {string|null}
	 */
	getUnit: function() {
		return this._unit;
	},

	/**
	 * Returns the quantity's lower boundary.
	 *
	 * @return {dataValues.DecimalValue|null}
	 */
	getLowerBound: function() {
		return this._lowerBound;
	},

	/**
	 * Returns the quantity's upper boundary.
	 *
	 * @return {dataValues.DecimalValue|null}
	 */
	getUpperBound: function() {
		return this._upperBound;
	},

	/**
	 * @inheritdoc
	 */
	equals: function( that ) {
		if ( !( that instanceof this.constructor ) ) {
			return false;
		}

		return this._amount.equals( that._amount )
			&& this._unit === that._unit
			&& ( this._upperBound === that._upperBound
				|| ( this._upperBound && this._upperBound.equals( that._upperBound ) ) )
			&& ( this._lowerBound === that._lowerBound
				|| ( this._lowerBound && this._lowerBound.equals( that._lowerBound ) ) );
	},

	/**
	 * @inheritdoc
	 *
	 * @return {Object}
	 */
	toJSON: function() {
		var json = {
			amount: this._amount.toJSON(),
			unit: this._unit
		};
		if ( this._upperBound && this._lowerBound ) {
			json.upperBound = this._upperBound.toJSON();
			json.lowerBound = this._lowerBound.toJSON();
		}
		return json;
	}
} );

/**
 * @inheritdoc
 *
 * @return {dataValues.QuantityValue}
 */
SELF.newFromJSON = function( json ) {
	return new SELF(
		new dv.DecimalValue( json.amount ),
		json.unit,
		json.upperBound ? new dv.DecimalValue( json.upperBound ) : null,
		json.lowerBound ? new dv.DecimalValue( json.lowerBound ) : null
	);
};

/**
 * @inheritdoc
 * @property {string} [TYPE='quantity']
 * @static
 */
SELF.TYPE = 'quantity';

dv.registerDataValue( SELF );

}( dataValues, util ) );

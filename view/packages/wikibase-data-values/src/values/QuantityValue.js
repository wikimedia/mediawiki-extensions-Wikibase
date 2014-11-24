dataValues.QuantityValue = ( function( dv, util ) {
	'use strict';

	var PARENT = dv.DataValue;

	/**
	 * Constructor for a data value representing a quantity.
	 * @licence GNU GPL v2+
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @since 0.1
	 *
	 * @param {dataValues.DecimalValue} amount Numeric string or a number.
	 * @param {string} unit A unit identifier. Must not be empty, use "1" for unit-less quantities.
	 * @param {dataValues.DecimalValue} upperBound The upper bound of the quantity, inclusive.
	 * @param {dataValues.DecimalValue} lowerBound The lower bound of the quantity, inclusive.
	 *
	 * @throws {Error} if constructor parameters are invalid.
	 */
	var constructor = function( amount, unit, upperBound, lowerBound ) {
		if( !amount || !( amount instanceof dv.DecimalValue ) ) {
			throw new Error( 'amount needs to be a DecimalValue object' );
		}

		if( typeof unit !== 'string' ) {
			throw new Error( 'unit must be of type string' );
		} else if( unit === '' ) {
			throw new Error( 'unit can not be an empty string (use "1" for unit-less quantities)' );
		}

		if( !lowerBound || !( lowerBound instanceof dv.DecimalValue ) ) {
			throw new Error( 'lowerBound needs to be a DecimalValue object' );
		}

		if( !upperBound || !( upperBound instanceof dv.DecimalValue ) ) {
			throw new Error( 'upperBound needs to be a DecimalValue object' );
		}

		this._amount = amount;
		this._unit = unit;
		this._lowerBound = lowerBound;
		this._upperBound = upperBound;
	};

	var QuantityValue = util.inherit( 'DvQuantityValue', PARENT, constructor, {
		/**
		 * @inheritdoc
		 *
		 * @since 0.1
		 *
		 * @return string
		 */
		getSortKey: function() {
			return this.getAmount().getValue();
		},

		/**
		 * @inheritdoc
		 * @since 0.1
		 *
		 * @return dataValues.QuantityValue
		 */
		getValue: function() {
			return this;
		},

		/**
		 * Returns the amount held by this quantity, as a string in standard format.
		 *
		 * @since 0.1
		 *
		 * @return string
		 */
		getAmount: function() {
			return this._amount;
		},

		/**
		 * Returns the unit held by this quantity. Returns null in case of unit-less quantities.
		 *
		 * @since 0.1
		 *
		 * @return string|null
		 */
		getUnit: function() {
			return this._unit;
		},

		/**
		 * Returns the quantity's lower boundary.
		 *
		 * @since 0.1
		 *
		 * @return {dataValues.DecimalValue|null}
		 */
		getLowerBound: function() {
			return this._lowerBound;
		},

		/**
		 * Returns the quantity's upper boundary.
		 *
		 * @since 0.1
		 *
		 * @return {dataValues.DecimalValue|null}
		 */
		getUpperBound: function() {
			return this._upperBound;
		},

		/**
		 * @inheritdoc
		 *
		 * @since 0.1
		 */
		equals: function( that ) {
			if ( !( that instanceof this.constructor ) ) {
				return false;
			}

			return this.getAmount().equals( that.getAmount() )
				&& this.getUnit() === that.getUnit()
				&& this.getLowerBound().equals( that.getLowerBound() )
				&& this.getUpperBound().equals( that.getUpperBound() );
		},

		/**
		 * @inheritdoc
		 *
		 * @since 0.1
		 *
		 * @return {Object}
		 */
		toJSON: function() {
			return {
				amount: this.getAmount().toJSON(),
				unit: this.getUnit(),
				upperBound: this.getUpperBound().toJSON(),
				lowerBound: this.getLowerBound().toJSON()
			};
		}
	} );

	/**
	 * @inheritdoc
	 */
	QuantityValue.newFromJSON = function( json ) {
		return new QuantityValue(
			new dv.DecimalValue( json.amount ),
			json.unit,
			new dv.DecimalValue( json.upperBound ),
			new dv.DecimalValue( json.lowerBound )
		);
	};

	/**
	 * @inheritdoc
	 */
	QuantityValue.TYPE = 'quantity';

	return QuantityValue;

}( dataValues, util ) );

dataValues.registerDataValue( dataValues.QuantityValue );

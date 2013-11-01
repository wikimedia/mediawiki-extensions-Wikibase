/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */
dataValues.QuantityValue = ( function( inherit, dv, $ ) {
	'use strict';

	var PARENT = dv.DataValue;

	/**
	 * Constructor for a data value representing a quantity.
	 *
	 * @since 0.1
	 *
	 * @param {string|number} amount Numeric string or a number.
	 * @param {string|null} [unit] A unit identifier, or null for unit-less quantities.
	 * @param {number} [significantDigits] The number of significant digits in the amount, counting
	 *        from the most significant digit, not counting the sign or decimal separator.
	 *
	 * @throws {Error}
	 */
	var constructor = function( amount, unit, significantDigits ) {
		if( typeof unit === 'number' ) {
			significantDigits = unit;
			unit = null;
		}
		var amount = processAmount( amount );
		this._unit = processUnit( unit );
		this._significantDigits = processSignificantDigits( significantDigits, amount );
		this._amount = enforceSignificanceOnAmount( amount, this._significantDigits );
	};

	function processAmount( amount ) {
		if( typeof amount === 'number' ) {
			amount = '' + amount;
			if( amount.indexOf( 'e' ) !== -1 ) {
				throw new Error( 'Can not cast huge numbers to the string format required for ' +
					'representing quantity values' );
			}
			// The next check will also handle NaN and Infinity.
		}

		if( typeof amount !== 'string'
			|| amount.match( /^[+-]?(?:[1-9]\d*|\d)(?:\.\d+)?$/ ) === null
		) {
			throw new Error(
				'Amount has to be a number or a numeric string using "." as decimal separator.' );
		}

		return amount.replace( /^(\d)/, '+$1' );
	}

	function processUnit( unit ) {
		if( unit === undefined ) {
			unit = null;
		}
		else if( unit !== null && typeof unit !== 'string' ) {
			throw new Error( 'The unit has to be a string or null' );
		}
		return unit;
	}

	function processSignificantDigits( significantDigits, amount ) {
		if( significantDigits === undefined ) {
			var hasDecimalSeparator = amount.indexOf( '.' ) !== -1;
			significantDigits = amount.length - 1 - hasDecimalSeparator;
		}
		else if( typeof significantDigits !== 'number' || significantDigits <= 0  ) {
			throw new Error( '' );
		}
		return significantDigits;
	}

	function enforceSignificanceOnAmount( amount, significantDigits ) {
		function padRight( string, desiredLength ) {
			while( string.length < desiredLength ) {
				string += '0';
			}
			return string;
		}

		var decimalSeparatorIndex = amount.indexOf( '.' );
		var hasDecimalSeparator = decimalSeparatorIndex !== -1;
		var significantChars = significantDigits + 1 + hasDecimalSeparator;
		var significantPart = amount.substr( 0, significantChars );

		if( significantChars > decimalSeparatorIndex ) {
			significantPart = significantPart.replace( /\.?$/, '.' );
		}
		significantPart = padRight( significantPart, decimalSeparatorIndex );
		return significantPart.replace( /\.$/, '' );
	}

	var QuantityValue = inherit( 'DvQuantityValue', PARENT, constructor, {
		/**
		 * @see dv.DataValue.getSortKey
		 *
		 * @since 0.1
		 *
		 * @return string
		 */
		getSortKey: function() {
			return this.getAmount();
		},

		/**
		 * Returns a self-reference.
		 * @see dv.DataValue.getValue
		 *
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
		 * Returns the number of significant digits in the amount, counting from the most
		 * significant digit, not counting the sign or decimal separator.
		 *
		 * @since 0.1
		 *
		 * @return number
		 */
		getSignificantDigits: function() {
			return this._significantDigits;
		},

		/**
		 * @see dv.DataValue.equals
		 *
		 * @since 0.1
		 */
		equals: function( value ) {
			if ( !( value instanceof this.constructor ) ) {
				return false;
			}

			return this.getAmount() === value.getAmount()
				&& this.getUnit() === value.getUnit()
				&& this.getSignificantDigits() === value.getSignificantDigits();
		},

		/**
		 * @see dv.DataValue.toJSON
		 *
		 * @since 0.1
		 *
		 * @return Object
		 */
		toJSON: function() {
			return {
				amount: this.getAmount(),
				unit: this.getUnit(),
				digits: this.getSignificantDigits()
			};
		}
	} );

	/**
	 * @see dv.DataValue.newFromJSON
	 */
	QuantityValue.newFromJSON = function( json ) {
		return new QuantityValue(
			json.amount,
			json.unit,
			json.digits
		);
	};

	/**
	 * @see dv.DataValue.TYPE
	 */
	QuantityValue.TYPE = 'quantity';

	return QuantityValue;

}( dataValues.util.inherit, dataValues, jQuery ) );

dataValues.registerDataValue( dataValues.StringValue );

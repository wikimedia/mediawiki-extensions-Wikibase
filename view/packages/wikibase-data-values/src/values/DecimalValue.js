dataValues.DecimalValue = ( function( dv, util ) {
	'use strict';

	var PARENT = dv.DataValue;

	/**
	 * Regular expression for matching decimal strings that conform to the format
	 * defined for DecimalValue.
	 */
	var DECIMAL_VALUE_PATTERN = /^[+-]?(?:[1-9]\d*|\d)(?:\.\d+)?$/;

	/**
	 * Constructor for a data value representing a decimal value.
	 * @class dataValues.DecimalValue
	 * @extends dataValues.DataValue
	 * @since 0.1
	 * @licence GNU GPL v2+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @param {string|number} value
	 */
	var constructor = function( value ) {
		if( typeof value === 'number' ) {
			value = convertToDecimalString( value );
		}

		assertDecimalString( value );

		this._value = value;
	};

	/**
	 * Converts a number to a string confirming to the DecimalValue definition.
	 *
	 * @param {number} number
	 * @return {string}
	 *
	 * @throws {Error} if number is invalid
	 */
	function convertToDecimalString( number ) {
		if( typeof number !== 'number' || !isFinite( number ) ) {
			throw new Error( 'Number is invalid (NaN or not finite)' );
		}

		var decimal = convertNumberToString( Math.abs( number ) );
		decimal = ( ( number < 0 ) ? '-' : '+' ) + decimal;

		assertDecimalString( decimal );

		return decimal;
	}

	/**
	 * Checks whether a string conforms to the DecimalValue definition.
	 *
	 * @param {string} decimalString
	 *
	 * @throws {Error} if string does not conform to the DecimalValue definition.
	 */
	function assertDecimalString( decimalString ) {
		if( typeof decimalString !== 'string' ) {
			throw new Error( 'Designated decimal string (' + decimalString + ') is not of type '
				+ 'string' );
		}

		if( !DECIMAL_VALUE_PATTERN.test( decimalString ) ) {
			throw new Error( 'Designated decimal string (' + decimalString + ' does not match the '
				+ 'pattern for numeric values' );
		}

		if( decimalString.length > 127 ) {
			throw new Error( 'Designated decimal string (' + decimalString + ') is longer than 127 '
				+ 'characters' );
		}
	}

	/**
	 * Converts a number of a string. This involves resolving the exponent (if any).
	 *
	 * @param {number} number
	 * @return {string}
	 */
	function convertNumberToString( number ) {
		var string = number.toString( 10 ),
			matches = string.match( /^(\d+)(\.(\d+))?e([-+]?)(\d+)$/i );

		if( !matches ) {
			return string;
		}

		var integerPart = Math.abs( matches[1] ),
			fractionalPart = matches[3] || '',
			sign = matches[4],
			exponent = matches[5],
			numberOfZeros = ( sign === '-' ) ? exponent - 1 : exponent - fractionalPart.length,
			zerosToPad = '';

		while( numberOfZeros-- ) {
			zerosToPad += '0';
		}

		string = ( sign === '-' )
			? '0.' + zerosToPad + integerPart + fractionalPart
			: integerPart + fractionalPart + zerosToPad;

		if( number < 0 ) {
			string = '-' + string;
		}

		return string;
	}

	var DecimalValue = util.inherit( 'DvDecimalValue', PARENT, constructor, {
		/**
		 * @inheritdoc
		 *
		 * @since 0.1
		 *
		 * @return {string}
		 */
		getSortKey: function() {
			return this._value;
		},

		/**
		 * @inheritdoc
		 *
		 * @since 0.1
		 *
		 * @return {string}
		 */
		getValue: function() {
			return this._value;
		},

		/**
		 * @inheritdoc
		 *
		 * @since 0.1
		 */
		equals: function( value ) {
			if ( !( value instanceof this.constructor ) ) {
				return false;
			}

			return this._value === value.getValue();
		},

		/**
		 * @inheritdoc
		 *
		 * @since 0.1
		 *
		 * @return {string}
		 */
		toJSON: function() {
			return this._value;
		}

	} );

	/**
	 * @inheritdoc
	 */
	DecimalValue.newFromJSON = function( json ) {
		return new DecimalValue( json );
	};

	/**
	 * @inheritdoc
	 */
	DecimalValue.TYPE = 'decimal';

	return DecimalValue;

}( dataValues, util ) );

dataValues.registerDataValue( dataValues.DecimalValue );

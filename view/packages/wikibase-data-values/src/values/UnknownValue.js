/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
( function( dv, util ) {
	'use strict';

	var PARENT = dv.DataValue,
		constructor = function( value ) {
			// TODO: validate
			this._value = value;
		};

	/**
	 * Constructor for creating a data value holding a value of unknown nature.
	 *
	 * @constructor
	 * @extends dv.DataValue
	 * @since 0.1
	 *
	 * @param {string} value
	 */
	dv.UnknownValue = util.inherit( 'DvUnknownValue', PARENT, constructor, {

		/**
		 * @see dv.DataValue.getSortKey
		 *
		 * @since 0.1
		 *
		 * @return number
		 */
		getSortKey: function() {
			return 0;
		},

		/**
		 * @see dv.DataValue.getValue
		 *
		 * @since 0.1
		 *
		 * @return string
		 */
		getValue: function() {
			return this._value;
		},

		/**
		 * @see dv.DataValue.equals
		 *
		 * Since the type of value is not known, it's not possible to perform
		 * an always correct and always meaningful comparison. Therefore false
		 * negatives might be returned.
		 *
		 * @since 0.1
		 */
		equals: function( value ) {
			if ( !( value instanceof dv.UnknownValue ) ) {
				return false;
			}

			return this.getValue() === value.getValue();
		},

		/**
		 * @see dv.DataValue.toJSON
		 *
		 * @since 0.1
		 */
		toJSON: function() {
			return this._value;
		}

	} );

	dv.UnknownValue.newFromJSON = function( json ) {
		return new dv.UnknownValue( json );
	};

	/**
	 * @see dv.DataValue.newFromJSON
	 */
	dv.UnknownValue.TYPE = 'unknown';

	/**
	 * @see dv.DataValue.TYPE
	 */
	dv.registerDataValue( dv.UnknownValue );

}( dataValues, util ) );

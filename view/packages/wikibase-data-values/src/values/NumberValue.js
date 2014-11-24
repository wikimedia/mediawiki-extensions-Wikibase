( function( dv, util ) {
	'use strict';

	var PARENT = dv.DataValue,
		constructor = function( value ) {
			// TODO: validate
			this._value = value;
		};

	/**
	 * Constructor for creating a data value representing a number.
	 * @licence GNU GPL v2+
	 * @author Daniel Werner < danweetz@web.de >
	 *
	 * @constructor
	 * @extends dv.DataValue
	 * @since 0.1
	 *
	 * @param {Number} value
	 */
	dv.NumberValue = util.inherit( 'DvNumberValue', PARENT, constructor, {
		/**
		 * @see dv.DataValue.getSortKey
		 *
		 * @return Number
		 */
		getSortKey: function() {
			return this._value;
		},

		/**
		 * @see dv.DataValue.getValue
		 *
		 * @return Number
		 */
		getValue: function() {
			return this._value;
		},

		/**
		 * @see dv.DataValue.equals
		 */
		equals: function( value ) {
			if ( !( value instanceof dv.NumberValue ) ) {
				return false;
			}

			return this.getValue() === value.getValue();
		},

		/**
		 * @see dv.DataValue.toJSON
		 */
		toJSON: function() {
			return this._value;
		}
	} );

	/**
	 * @see dv.DataValue.newFromJSON
	 */
	dv.NumberValue.newFromJSON = function( json ) {
		return new dv.NumberValue( json );
	};

	/**
	 * @see dv.DataValue.TYPE
	 */
	dv.NumberValue.TYPE = 'number';

	// make this data value available in the store:
	dv.registerDataValue( dv.NumberValue );

}( dataValues, util ) );

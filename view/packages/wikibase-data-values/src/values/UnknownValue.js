( function( dv, util ) {
	'use strict';

	var PARENT = dv.DataValue;

	/**
	 * Constructor for creating a data value holding a value of unknown nature.
	 * @class dataValues.UnknownValue
	 * @extends dataValues.DataValue
	 * @since 0.1
	 * @licence GNU GPL v2+
	 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
	 *
	 * @constructor
	 *
	 * @param {*} value
	 */
	var SELF = dv.UnknownValue = util.inherit( 'DvUnknownValue', PARENT, function( value ) {
		// TODO: validate
		this._value = value;
	}, {
		/**
		 * @property {*}
		 */
		_value: null,

		/**
		 * @inheritdoc
		 *
		 * @return {number}
		 */
		getSortKey: function() {
			return 0;
		},

		/**
		 * @inheritdoc
		 *
		 * @return {*}
		 */
		getValue: function() {
			return this._value;
		},

		/**
		 * Since the type of value is not known, it's not possible to perform a comparison always
		 * correct and meaningful. Therefore, false negatives might be returned.
		 * @inheritdoc
		 */
		equals: function( value ) {
			if ( !( value instanceof dv.UnknownValue ) ) {
				return false;
			}

			return this.getValue() === value.getValue();
		},

		/**
		 * @inheritdoc
		 *
		 * @return {*}
		 */
		toJSON: function() {
			return this._value;
		}

	} );

	/**
	 * @inheritdoc
	 *
	 * @return {dataValues.UnknownValue}
	 */
	SELF.newFromJSON = function( json ) {
		return new SELF( json );
	};

	/**
	 * @inheritdoc
	 * @property {string} [TYPE='unknown']
	 */
	SELF.TYPE = 'unknown';

	dv.registerDataValue( SELF );

}( dataValues, util ) );

( function( dv, util ) {
'use strict';

var PARENT = dv.DataValue,
	constructor = function( value ) {
		if( typeof value !== 'string' ) {
			throw new Error( 'A string value has to be given' );
		}
		this._value = value;
	};

/**
 * Constructor for creating a data value representing a string.
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 *
 * @constructor
 * @extends dv.DataValue
 * @since 0.1
 *
 * @param {string} value
 */
dv.StringValue = util.inherit( 'DvStringValue', PARENT, constructor, {

	/**
	 * @inheritdoc
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	getSortKey: function() {
		return this._value;
	},

	/**
	 * @inheritdoc
	 *
	 * @since 0.1
	 *
	 * @return string
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
		if ( !( value instanceof dv.StringValue ) ) {
			return false;
		}

		return this.getValue() === value.getValue();
	},

	/**
	 * @inheritdoc
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	toJSON: function() {
		return this._value;
	}

} );

/**
 * @inheritdoc
 */
dv.StringValue.newFromJSON = function( json ) {
	return new dv.StringValue( json );
};

/**
 * @inheritdoc
 */
dv.StringValue.TYPE = 'string';

dv.registerDataValue( dv.StringValue );

}( dataValues, util ) );

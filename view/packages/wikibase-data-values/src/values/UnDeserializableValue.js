( function( dv, util, $ ) {
	'use strict';

var PARENT = dv.DataValue;

/**
 * Constructor for creating a data value representing a value which could not have been unserialized
 * for some reason. Holds the serialized value which can not be unserialized as well as an error
 * object describing the reason why the value can not be unserialized properly.
 * @class dataValues.UnDeserializableValue
 * @extends dataValues.DataValue
 * @since 0.1
 * @license GPL-2.0+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 *
 * @constructor
 *
 * @param {string} ofType The data value type the structure should have been deserialized to.
 * @param {Object} unDeserializableStructure Plain object assumingly representing some data value
 *        but the responsible deserializer was not able to deserialize it.
 * @param {string} deserializeError The error thrown during the attempt to deserialize the given
 *        structure.
 */
var SELF = dv.UnDeserializableValue = util.inherit(
	'DvUnDeserializableValue',
	PARENT,
	function( unDeserializableStructure, ofType, deserializeError ) {
		if( !$.isPlainObject( unDeserializableStructure ) ) {
			throw new Error( 'The undeserializable structure has to be a plain object.' );
		}
		if( typeof ofType !== 'string' ) {
			throw new Error( 'The undeserializable type must be a string.' );
		}
		if( typeof deserializeError !== 'string' ) {
			throw new Error( 'The undeserializable error param must be a string.' );
		}
		this._unDeserializableStructure = $.extend( {}, unDeserializableStructure );
		this._targetType = ofType;
		this._deserializeError = deserializeError;
	},
{
	/**
	 * @property {Object}
	 * @private
	 */
	_unDeserializableStructure: null,

	/**
	 * @property {string}
	 * @private
	 */
	_targetType: null,

	/**
	 * @property {string}
	 * @private
	 */
	_deserializeError: null,

	/**
	 * @inheritdoc
	 *
	 * @return {string}
	 */
	getSortKey: function() {
		return this.getReason();
	},

	/**
	 * @inheritdoc
	 *
	 * @return {dataValues.UnDeserializableValue}
	 */
	getValue: function() {
		return this;
	},

	/**
	 * Returns the structure not possible to deserialize.
	 *
	 * @return {Object}
	 */
	getStructure: function() {
		return $.extend( {}, this._unDeserializableStructure );
	},

	/**
	 * Returns the data value type into which the structure should have been deserialized.
	 *
	 * @return {string}
	 */
	getTargetType: function() {
		return this._targetType;
	},

	/**
	 * Returns the error object stating why some deserializer was not able to deserialize the
	 * structure.
	 *
	 * @return {string}
	 */
	getReason: function() {
		return this._deserializeError;
	},

	/**
	 * @inheritdoc
	 */
	equals: function( other ) {
		if( !( other instanceof SELF ) ) {
			return false;
		}

		return JSON.stringify( this.toJSON() ) === JSON.stringify( other.toJSON() );
	},

	/**
	 * @inheritdoc
	 */
	toJSON: function() {
		return {
			value: this.getStructure(),
			type: this.getTargetType(),
			error: this.getReason()
		};
	}
} );

/**
 * @inheritdoc
 */
SELF.newFromJSON = function( json ) {
	return new SELF(
		json.value,
		json.type,
		json.error
	);
};

/**
 * @inheritdoc
 * @property {string} [TYPE='undeserializable']
 * @static
 */
SELF.TYPE = 'undeserializable';

// NOTE: we don't have to register this one globally since this one is constructed on demand rather
//  than being provided by some store or builder.
dv.registerDataValue( SELF );

}( dataValues, util, jQuery ) );

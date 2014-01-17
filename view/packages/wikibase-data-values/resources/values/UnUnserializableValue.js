/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( dv, util, $ ) {
	'use strict';

	var PARENT = dv.DataValue,
		constructor = function( unUnserializableStructure, ofType, unserializeError ) {
			if( !$.isPlainObject( unUnserializableStructure ) ) {
				throw new Error( 'The un-unserializable structure has to be a plain object' );
			}
			if( typeof ofType !== 'string' ) {
				throw new Error( '' );
			}
			if( !unserializeError || !( unserializeError instanceof Error ) ) {
				throw new Error( 'No Error object given' );
			}
			this._unUnserializableStructure = $.extend( {}, unUnserializableStructure );
			this._targetType = ofType;
			this._unserializeError = unserializeError;
		};

	/**
	 * Constructor for creating a data value representing a value which could not have been
	 * unserialized for some reason. Holds the serialized value which can not be unserialized as
	 * well as an error object describing the reason why the value can not be unserialized properly.
	 *
	 * @constructor
	 * @extends dv.DataValue
	 * @since 0.1
	 *
	 * @param {Object} unUnserializableStructure Plain object assumingly representing some data
	 *        value but the responsible unserializer was not able to unserialize it.
	 * @param {string} ofType The data value type the structure should have been unserialized to.
	 * @param {Error} unserializeError The error thrown during the attempt to unserialize the given
	 *        structure.
	 */
	var SELF = dv.UnUnserializableValue = util.inherit( 'DvUnUnserializableValue', PARENT, constructor, {
		/**
		 * @see dv.DataValue.getSortKey
		 *
		 * @since 0.1
		 *
		 * @return String
		 */
		getSortKey: function() {
			return this.getReason().name;
		},

		/**
		 * @see dv.DataValue.getValue
		 *
		 * @since 0.1
		 *
		 * @return dataValues.UnUnserializableValue
		 */
		getValue: function() {
			return this;
		},

		/**
		 * Returns the structure not possible to unserialize.
		 *
		 * @since 0.1
		 *
		 * @return Object
		 */
		getStructure: function() {
			return $.extend( {}, this._unUnserializableStructure );
		},

		/**
		 * Returns the data value type into which the structure should have been unserialized.
		 *
		 * @returns string
		 */
		getTargetType: function() {
			return this._targetType;
		},

		/**
		 * Returns the error object stating why some unserializer was not able to unserialize the
		 * structure.
		 *
		 * @since 0.1
		 *
		 * @returns Error
		 */
		getReason: function() {
			return this._unserializeError;
		},

		/**
		 * @see dv.DataValue.equals
		 *
		 * @since 0.1
		 */
		equals: function( other ) {
			// TODO: Do deep equal of the structures and reasons instead.
			return this === other;
		},

		/**
		 * @see dv.DataValue.toJSON
		 *
		 * @since 0.1
		 */
		toJSON: function() {
			// TODO
			throw new Error( 'Not implemented yet.' );
		}
	} );

	/**
	 * @see dv.DataValue.newFromJSON
	 */
	SELF.newFromJSON = function( json ) {
		// TODO
		throw new Error( 'Not implemented yet.' );
	};

	/**
	 * @see dv.DataValue.TYPE
	 */
	SELF.TYPE = 'ununserializable';

	// NOTE: we don't have to register this one globally since this one is constructed on demand
	//  rather than being constructed by some factory or builder.
	//dv.registerDataValue( SELF );

}( dataValues, util, jQuery ) );

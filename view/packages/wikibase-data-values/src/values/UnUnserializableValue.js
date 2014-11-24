( function( dv, util, $ ) {
	'use strict';

	var PARENT = dv.DataValue;

	/**
	 * Constructor for creating a data value representing a value which could not have been
	 * unserialized for some reason. Holds the serialized value which can not be unserialized as
	 * well as an error object describing the reason why the value can not be unserialized properly.
	 * @class dataValues.UnUnserializableValue
	 * @extends dataValues.DataValue
	 * @since 0.1
	 * @licence GNU GPL v2+
	 * @author Daniel Werner < daniel.werner@wikimedia.de >
	 *
	 * @constructor
	 *
	 * @param {Object} unUnserializableStructure Plain object assumingly representing some data
	 *        value but the responsible unserializer was not able to unserialize it.
	 * @param {string} ofType The data value type the structure should have been unserialized to.
	 * @param {Error} unserializeError The error thrown during the attempt to unserialize the given
	 *        structure.
	 */
	var SELF = dv.UnUnserializableValue = util.inherit(
		'DvUnUnserializableValue',
		PARENT,
		function( unUnserializableStructure, ofType, unserializeError ) {
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
		},
	{
		/**
		 * @property {Object}
		 * @private
		 */
		_unUnserializableStructure: null,

		/**
		 * @property {string}
		 * @private
		 */
		_targetType: null,

		/**
		 * @property {Error}
		 * @private
		 */
		_unserializeError: null,

		/**
		 * @inheritdoc
		 *
		 * @return {string}
		 */
		getSortKey: function() {
			return this.getReason().name;
		},

		/**
		 * @inheritdoc
		 *
		 * @return {dataValues.UnUnserializableValue}
		 */
		getValue: function() {
			return this;
		},

		/**
		 * Returns the structure not possible to unserialize.
		 *
		 * @return {Object}
		 */
		getStructure: function() {
			return $.extend( {}, this._unUnserializableStructure );
		},

		/**
		 * Returns the data value type into which the structure should have been unserialized.
		 *
		 * @return {string}
		 */
		getTargetType: function() {
			return this._targetType;
		},

		/**
		 * Returns the error object stating why some unserializer was not able to unserialize the
		 * structure.
		 *
		 * @return {Error}
		 */
		getReason: function() {
			return this._unserializeError;
		},

		/**
		 * @inheritdoc
		 */
		equals: function( other ) {
			// TODO: Do deep equal of the structures and reasons instead.
			return this === other;
		},

		/**
		 * @inheritdoc
		 *
		 * @throws {Error} whenever the function is called (implementation missing).
		 */
		toJSON: function() {
			// TODO
			throw new Error( 'Not implemented yet.' );
		}
	} );

	/**
	 * @inheritdoc
	 *
	 * @throws {Error} whenever the function is called (implementation missing).
	 */
	SELF.newFromJSON = function( json ) {
		// TODO
		throw new Error( 'Not implemented yet.' );
	};

	/**
	 * @inheritdoc
	 * @property {string} [TYPE='ununserializable']
	 */
	SELF.TYPE = 'ununserializable';

	// NOTE: we don't have to register this one globally since this one is constructed on demand
	//  rather than being provided by some store or builder.
	//dv.registerDataValue( SELF );

}( dataValues, util, jQuery ) );

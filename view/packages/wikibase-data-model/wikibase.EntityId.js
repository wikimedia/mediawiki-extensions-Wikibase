/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
( function( wb, dv, $, undefined ) {
	'use strict';

	var PARENT = dv.DataValue;

	/**
	 * Represents the ID of an Entity.
	 * @constructor
	 * @since 0.4
	 *
	 * @param {String} entityType
	 * @param {Number} numericId
	 */
	var constructor = function( entityType, numericId ) {
		if( typeof( entityType ) !== 'string' ) {
			throw new Error( 'entityType is required for constructing new EntityId and must be a string' );
		}

		if( typeof( numericId ) !== 'number' ) {
			throw new Error( 'numericId is required for constructing new EntityId and must be an int' );
		}

		this._entityType = entityType;
		this._numericId = numericId;
	};

	wb.EntityId = dv.util.inherit( PARENT, constructor, {

		/**
		 * @type String
		 */
		_entityType: null,

		/**
		 * @type Number
		 */
		_numericId: null,

		/**
		 * Returns the type of the entity.
		 *
		 * @since 0.4

		 * @return String
		 */
		getEntityType: function() {
			return this._entityType;
		},

		/**
		 * Returns the numeric id of the entity.
		 *
		 * @since 0.4
		 *
		 * @return Number
		 */
		getNumericId: function() {
			return this._numericId;
		},

		/**
		 * Returns whether this EntityId is equal to another EntityId.
		 *
		 * @since 0.4
		 *
		 * @param {wb.EntityId} entityId
		 *
		 * @return Boolean
		 */
		equals: function( entityId ) {
			if ( entityId === this ) {
				return true;
			}

			return this.getEntityType() === entityId.getEntityType() && this.getNumericId() === entityId.getNumericId();
		},

		/**
		 * @see dv.DataValue.getValue
		 *
		 * @since 0.4
		 *
		 * @return {*}
		 */
		getValue: function() {
			return this;
		},

		/**
		 * @see dv.DataValue.getSortKey
		 *
		 * @since 0.4
		 *
		 * @return String|Number
		 */
		getSortKey: function() {
			return this._entityType + this._numericId;
		},

		/**
		 * @see dv.DataValue.toJSON
		 *
		 * @since 0.4
		 *
		 * @return Object
		 */
		toJSON: function() {
			return {
				'entity-type': this._entityType,
				'numeric-id': this._numericId
			};
		}

	} );

wb.EntityId.newFromJSON = function( json ) {
	return new wb.EntityId( json['entity-type'], json['numeric-id'] );
};

wb.EntityId.TYPE = 'wikibase-entityid';

dv.registerDataValue( wb.EntityId );

}( wikibase, dataValues, jQuery ) );

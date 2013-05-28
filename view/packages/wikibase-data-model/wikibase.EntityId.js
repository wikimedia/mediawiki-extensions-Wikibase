/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
( function( wb, dv ) {
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
		if( typeof entityType !== 'string' ) {
			throw new Error( 'entityType is required for constructing new EntityId and must be a string' );
		}

		if( typeof numericId !== 'number' ) {
			throw new Error( 'numericId is required for constructing new EntityId and must be a number' );
		}

		this._entityType = entityType;
		this._numericId = numericId;
	};

	wb.EntityId = dv.util.inherit( 'WbEntityId', PARENT, constructor, {

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
		 * Returns the numeric ID of the entity.
		 *
		 * @since 0.4
		 *
		 * @return Number
		 */
		getNumericId: function() {
			return this._numericId;
		},

		/**
		 * Returns the prefixed ID of the entity. Requires a map for formatting the prefix.
		 *
		 * @since 0.4
		 *
		 * @param {Object} prefixMap Like { prefix: entityType }, e.g. { 'p': 'property' }
		 *        The same entity type can appear multiple times with different prefixes. If this is
		 *        the case, the first one will be taken.
		 * @return String
		 */
		getPrefixedId: function( prefixMap ) {
			var entityType = this._entityType;

			// find prefix of this entity ID's entity type
			for( var key in prefixMap ) {
				if( prefixMap[ key ] === entityType ) {
					return key + this.getNumericId();
				}
			}

			// can't output prefixed ID without knowing the prefix!
			throw new Error( 'The given prefix map does not contain a prefix for the entitytype "' +
				entityType + '"' );
		},

		/**
		 * @see dv.DataValue.equals
		 *
		 * @since 0.4
		 */
		equals: function( entityId ) {
			if ( entityId === this ) {
				return true;
			}
			if( !( entityId instanceof this.constructor ) ) {
				return false;
			}

			return this.getEntityType() === entityId.getEntityType()
				&& this.getNumericId() === entityId.getNumericId();
		},

		/**
		 * @see dv.DataValue.getValue
		 *
		 * @since 0.4
		 *
		 * @return wb.EntityId
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

/**
 * @see dv.DataValue.newFromJSON
 */
wb.EntityId.newFromJSON = function( json ) {
	return new wb.EntityId( json['entity-type'], json['numeric-id'] );
};

wb.EntityId.TYPE = 'wikibase-entityid';

dv.registerDataValue( wb.EntityId );

}( wikibase, dataValues ) );

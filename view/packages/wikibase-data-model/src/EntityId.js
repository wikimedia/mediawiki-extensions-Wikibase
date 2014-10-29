/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
( function( wb, dv, util ) {
	'use strict';

var PARENT = dv.DataValue;

/**
 * @constructor
 * @since 0.3
 *
 * @param {string} entityType
 * @param {number} numericId
 */
wb.datamodel.EntityId = util.inherit(
	'WbDataModelEntityId',
	PARENT,
	function( entityType, numericId ) {
		if( typeof entityType !== 'string' ) {
			throw new Error( 'entityType is required for constructing a EntityId and must be a '
				+ 'string' );
		}
		if( typeof numericId !== 'number' ) {
			throw new Error( 'numericId is required for constructing a EntityId and must be a '
				+ 'number' );
		}

		this._entityType = entityType;
		this._numericId = numericId;
	},
{
	/**
	 * @type {string}
	 */
	_entityType: null,

	/**
	 * @type {number}
	 */
	_numericId: null,

	/**
	 * @return {string}
	 */
	getEntityType: function() {
		return this._entityType;
	},

	/**
	 * @return {number}
	 */
	getNumericId: function() {
		return this._numericId;
	},

	/**
	 * @param {Object} prefixMap Like { prefix: entityType }, e.g. { 'P': 'property' }
	 *        If the same entity type appears multiple times with different prefixes, the prefix
	 *        found first will be applied.
	 * @return {string}
	 */
	getPrefixedId: function( prefixMap ) {
		var entityType = this._entityType;

		// Find prefix of this entity ID's entity type:
		for( var key in prefixMap ) {
			if( prefixMap[key] === entityType ) {
				return key + this.getNumericId();
			}
		}

		throw new Error( 'Supplied prefix map does not contain a prefix for the entity type "' +
			entityType + '"' );
	},

	/**
	 * @see dataValues.DataValue.equals
	 */
	equals: function( entityId ) {
		return entityId === this
			|| entityId instanceof this.constructor
				&& this.getEntityType() === entityId.getEntityType()
				&& this.getNumericId() === entityId.getNumericId();
	},

	/**
	 * @see dataValues.DataValue.getValue
	 *
	 * @return {wikibase.datamodel.EntityId}
	 */
	getValue: function() {
		return this;
	},

	/**
	 * @see dataValues.DataValue.getSortKey
	 */
	getSortKey: function() {
		return this._entityType + this._numericId;
	},

	/**
	 * @see dataValues.DataValue.toJSON
	 */
	toJSON: function() {
		return {
			'entity-type': this._entityType,
			'numeric-id': this._numericId
		};
	}
} );

/**
 * @see dataValues.DataValue.newFromJSON
 */
wb.datamodel.EntityId.newFromJSON = function( json ) {
	return new wb.datamodel.EntityId( json['entity-type'], json['numeric-id'] );
};

wb.datamodel.EntityId.TYPE = 'wikibase-entityid';

dv.registerDataValue( wb.datamodel.EntityId );

}( wikibase, dataValues, util ) );

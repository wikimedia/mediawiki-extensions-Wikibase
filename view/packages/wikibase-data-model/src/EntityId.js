( function( wb, dv, util ) {
	'use strict';

var PARENT = dv.DataValue;

/**
 * EntityId data value.
 * @class wikibase.datamodel.EntityId
 * @extends dataValues.DataValue
 * @since 0.3
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 *
 * @constructor
 *
 * @param {string} entityType
 * @param {number} numericId
 *
 * @throws {Error} if a required parameter is not specified properly.
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
	 * @property {string}
	 * @private
	 */
	_entityType: null,

	/**
	 * @property {number}
	 * @private
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
	 *
	 * @throws {Error} when the prefix map does not contain a prefix for the entity type set on the
	 *         object.
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
	 * @inheritdoc
	 */
	equals: function( entityId ) {
		return entityId === this
			|| entityId instanceof this.constructor
				&& this.getEntityType() === entityId.getEntityType()
				&& this.getNumericId() === entityId.getNumericId();
	},

	/**
	 * @inheritdoc
	 *
	 * @return {wikibase.datamodel.EntityId}
	 */
	getValue: function() {
		return this;
	},

	/**
	 * @inheritdoc
	 */
	getSortKey: function() {
		return this._entityType + this._numericId;
	},

	/**
	 * @inheritdoc
	 */
	toJSON: function() {
		return {
			'entity-type': this._entityType,
			'numeric-id': this._numericId
		};
	}
} );

/**
 * @inheritdoc
 * @static
 *
 * @return {wikibase.datamodel.EntityId}
 */
wb.datamodel.EntityId.newFromJSON = function( json ) {
	return new wb.datamodel.EntityId( json['entity-type'], json['numeric-id'] );
};

/**
 * @inheritdoc
 * @property {string} [TYPE='wikibase-entityid']
 */
wb.datamodel.EntityId.TYPE = 'wikibase-entityid';

dv.registerDataValue( wb.datamodel.EntityId );

}( wikibase, dataValues, util ) );

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, dv, util ) {
	'use strict';

var PARENT = dv.DataValue;

/**
 * @constructor
 * @since 0.3
 *
 * @param {string} entityType
 * @param {string} serialization
 */
wb.datamodel.EntityId = util.inherit(
	'WbDataModelEntityId',
	PARENT,
	function( entityType, serialization ) {
		if( typeof entityType !== 'string' ) {
			throw new Error( 'entityType needs to be specified as a string' );
		} else if( typeof serialization !== 'string' ) {
			throw new Error( 'serialization needs to be specified as a string' );
		}

		this._entityType = entityType;
		this._serialization = serialization;
	},
{
	/**
	 * @type {string}
	 */
	_entityType: null,

	/**
	 * @type {string}
	 */
	_serialization: null,

	/**
	 * @return {string}
	 */
	getEntityType: function() {
		return this._entityType;
	},

	/**
	 * @return {string}
	 */
	getSerialization: function() {
		return this._serialization;
	},

	/**
	 * @see dataValues.DataValue.equals
	 */
	equals: function( entityId ) {
		return entityId === this
			|| entityId instanceof this.constructor
				&& this._entityType === entityId.getEntityType()
				&& this._serialization === entityId.getSerialization();
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
	 *
	 * @return {string}
	 */
	getSortKey: function() {
		return this._serialization;
	},

	/**
	 * @see dataValues.DataValue.toJSON
	 *
	 * @return {Object}
	 */
	toJSON: function() {
		return [this._entityType, this._serialization];
	}
} );

/**
 * @see dataValues.DataValue.newFromJSON
 */
wb.datamodel.EntityId.newFromJSON = function( json ) {
	return new wb.datamodel.EntityId( json[0], json[1] );
};

wb.datamodel.EntityId.TYPE = 'wikibase-entityid';

dv.registerDataValue( wb.datamodel.EntityId );

}( wikibase, dataValues, util ) );

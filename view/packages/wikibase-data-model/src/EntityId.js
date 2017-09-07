( function( wb, dv, util ) {
	'use strict';

var PARENT = dv.DataValue;

/**
 * EntityId data value.
 * @class wikibase.datamodel.EntityId
 * @extends dataValues.DataValue
 * @since 0.3
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 *
 * @constructor
 *
 * @param {string} serialization
 *
 * @throws {Error} if a required parameter is not specified properly.
 */
wb.datamodel.EntityId = util.inherit(
	'WbDataModelEntityId',
	PARENT,
	function( serialization ) {
		if( typeof serialization !== 'string' ) {
			throw new Error( 'serialization is required for constructing a EntityId and must be a '
				+ 'string' );
		}

		this._serialization = serialization;
	},
{
	/**
	 * @property {string}
	 * @private
	 */
	_serialization: null,

	/**
	 * @return {string}
	 */
	getSerialization: function() {
		return this._serialization;
	},

	/**
	 * @inheritdoc
	 */
	equals: function( entityId ) {
		return entityId === this
			|| entityId instanceof this.constructor
				&& this.getSerialization() === entityId.getSerialization();
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
	toJSON: function() {
		return {
			'id': this._serialization
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
	return new wb.datamodel.EntityId( json.id );
};

/**
 * @inheritdoc
 * @property {string} [TYPE='wikibase-entityid']
 */
wb.datamodel.EntityId.TYPE = 'wikibase-entityid';

dv.registerDataValue( wb.datamodel.EntityId );

}( wikibase, dataValues, util ) );

( function( wb, util ) {
	'use strict';

var PARENT = wb.datamodel.FingerprintableEntity;

/**
 * Entity derivative featuring a data type and statements.
 * @class wikibase.datamodel.Property
 * @extends wikibase.datamodel.FingerprintableEntity
 * @since 1.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {string} entityId
 * @param {string} dataTypeId
 * @param {wikibase.datamodel.Fingerprint|null} [fingerprint=new wikibase.datamodel.Fingerprint()]
 * @param {wikibase.datamodel.StatementGroupSet|null} [statementGroupSet=new wikibase.datamodel.StatementGroupSet()]
 *
 * @throws {Error} if a required parameter is not specified properly.
 */
var SELF = wb.datamodel.Property = util.inherit(
	'WbDataModelProperty',
	PARENT,
	function( entityId, dataTypeId, fingerprint, statementGroupSet ) {
		fingerprint = fingerprint || new wb.datamodel.Fingerprint();
		statementGroupSet = statementGroupSet || new wb.datamodel.StatementGroupSet();

		if(
			typeof entityId !== 'string'
			|| typeof dataTypeId !== 'string'
			|| !( fingerprint instanceof wb.datamodel.Fingerprint )
			|| !( statementGroupSet instanceof wb.datamodel.StatementGroupSet )
		) {
			throw new Error( 'Required parameter(s) missing or not defined properly' );
		}

		this._id = entityId;
		this._fingerprint = fingerprint;
		this._dataTypeId = dataTypeId;
		this._statementGroupSet = statementGroupSet;
	},
{
	/**
	 * @property {string}
	 * @private
	 */
	_dataTypeId: null,

	/**
	 * @property {wikibase.datamodel.StatementGroupSet}
	 * @private
	 */
	_statementGroupSet: null,

	/**
	 * @return {string}
	 */
	getDataTypeId: function() {
		return this._dataTypeId;
	},

	/**
	 * @return {wikibase.datamodel.StatementGroupSet}
	 */
	getStatements: function() {
		return this._statementGroupSet;
	},

	/**
	 * @return {boolean}
	 */
	isEmpty: function() {
		return this._fingerprint.isEmpty() && this._statementGroupSet.isEmpty();
	},

	/**
	 * @param {*} property
	 * @return {boolean}
	 */
	equals: function( property ) {
		return property === this
			|| property instanceof SELF
				&& this._id === property.getId()
				&& this._dataTypeId === property.getDataTypeId()
				&& this._fingerprint.equals( property.getFingerprint() )
				&& this._statementGroupSet.equals( property.getStatements() );
	}
} );

/**
 * @inheritdoc
 * @property {string} [TYPE='property']
 * @static
 */
SELF.TYPE = 'property';

}( wikibase, util ) );

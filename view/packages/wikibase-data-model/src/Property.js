/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var PARENT = wb.datamodel.Entity;

/**
 * @constructor
 * @extends wikibase.datamodel.Entity
 * @since 1.0
 *
 * @param {string} entityId
 * @param {string} dataTypeId
 * @param {wikibase.datamodel.Fingerprint|null} [fingerprint]
 * @param {wikibase.datamodel.StatementGroupSet|null} [statementGroupSet]
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
	 * @type {string}
	 */
	_dataTypeId: null,

	/**
	 * @type {wikibase.datamodel.StatementGroupSet}
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
	 * @param {wikibase.datamodel.Statement} statement
	 */
	addStatement: function( statement ) {
		this._statementGroupSet.addStatement( statement );
	},

	/**
	 * @param {wikibase.datamodel.Statement} statement
	 */
	removeStatement: function( statement ) {
		this._statementGroupSet.removeStatement( statement );
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
 * @see wikibase.datamodel.Entity.TYPE
 */
SELF.TYPE = 'property';

}( wikibase, util ) );

/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var PARENT = wb.datamodel.Entity;

/**
 * Represents a Wikibase Property.
 * @constructor
 * @extends wikibase.datamodel.Entity
 * @since 0.4
 *
 * @param {string} entityId
 * @param {string} dataTypeId
 * @param {wikibase.datamodel.Fingerprint|null} [fingerprint]
 * @param {wikibase.datamodel.StatementGroupList|null} [statementGroupList]
 */
var SELF = wb.datamodel.Property = util.inherit(
	'WbProperty',
	PARENT,
	function( entityId, dataTypeId, fingerprint, statementGroupList ) {
		fingerprint = fingerprint || new wb.datamodel.Fingerprint();
		statementGroupList = statementGroupList || new wb.datamodel.StatementGroupList();

		if(
			typeof entityId !== 'string'
			|| typeof dataTypeId !== 'string'
			|| !( fingerprint instanceof wb.datamodel.Fingerprint )
			|| !( statementGroupList instanceof wb.datamodel.StatementGroupList )
		) {
			throw new Error( 'Required parameter(s) missing or not defined properly' );
		}

		this._id = entityId;
		this._fingerprint = fingerprint;
		this._dataTypeId = dataTypeId;
		this._statementGroupList = statementGroupList;
	},
{
	/**
	 * @type {string}
	 */
	_dataTypeId: null,

	/**
	 * @type {wikibase.datamodel.StatementGroupList}
	 */
	_statementGroupList: null,

	/**
	 * @return {string}
	 */
	getDataTypeId: function() {
		return this._dataTypeId;
	},

	/**
	 * @return {wikibase.datamodel.StatementGroupList}
	 */
	getStatements: function() {
		return this._statementGroupList;
	},

	/**
	 * @param {wikibase.datamodel.Statement} statement
	 */
	addStatement: function( statement ) {
		this._statementGroupList.addStatement( statement );
	},

	/**
	 * @param {wikibase.datamodel.Statement} statement
	 */
	removeStatement: function( statement ) {
		this._statementGroupList.removeStatement( statement );
	},

	/**
	 * @return {boolean}
	 */
	isEmpty: function() {
		return this._fingerprint.isEmpty() && this._statementGroupList.isEmpty();
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
				&& this._statementGroupList.equals( property.getStatements() );
	}
} );


/**
 * @see wikibase.datamodel.Entity.TYPE
 */
SELF.TYPE = 'property';

}( wikibase, util ) );

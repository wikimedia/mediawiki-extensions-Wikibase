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
 * @param {wikibase.datamodel.StatementList|null} [statementList]
 */
var SELF = wb.datamodel.Property = util.inherit(
	'WbProperty',
	PARENT,
	function( entityId, dataTypeId, fingerprint, statementList ) {
		fingerprint = fingerprint || new wb.datamodel.Fingerprint();
		statementList = statementList || new wb.datamodel.StatementList();

		if(
			typeof entityId !== 'string'
				|| !( fingerprint instanceof wb.datamodel.Fingerprint )
				|| typeof dataTypeId !== 'string'
				|| !( statementList instanceof wb.datamodel.StatementList )
		) {
			throw new Error( 'Required parameter(s) missing or not defined properly' );
		}

		this._id = entityId;
		this._fingerprint = fingerprint;
		this._dataTypeId = dataTypeId;
		this._statementList = statementList;
	},
{
	/**
	 * @type {string}
	 */
	_dataTypeId: null,

	/**
	 * @type {wikibase.datamodel.StatementList}
	 */
	_statementList: null,

	/**
	 * @return {string}
	 */
	getDataTypeId: function() {
		return this._dataTypeId;
	},

	/**
	 * @return {wikibase.datamodel.StatementList}
	 */
	getStatements: function() {
		return this._statementList;
	},

	/**
	 * @param {wikibase.datamodel.Statement} statement
	 */
	addStatement: function( statement ) {
		this._statementList.addStatement( statement );
	},

	/**
	 * @param {wikibase.datamodel.Statement} statement
	 */
	removeStatement: function( statement ) {
		this._statementList.removeStatement( statement );
	},

	/**
	 * @return {boolean}
	 */
	isEmpty: function() {
		return this._fingerprint.isEmpty() && this._statementList.isEmpty();
	},

	/**
	 * @param {*} property
	 * @return {boolean}
	 */
	equals: function( property ) {
		if( !( property instanceof SELF ) ) {
			return false;
		} else if( property === this ) {
			return true;
		}

		return this._id === property.getId()
			&& this._fingerprint.equals( property.getFingerprint() )
			&& this._dataTypeId === property.getDataTypeId();
	}
} );


/**
 * @see wikibase.datamodel.Entity.TYPE
 */
SELF.TYPE = 'property';

}( wikibase, util ) );

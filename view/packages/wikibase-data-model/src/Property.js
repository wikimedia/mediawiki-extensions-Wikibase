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
 * @param {wikibase.datamodel.Fingerprint} fingerprint
 * @param {string} dataTypeId
 * @param {wikibase.datamodel.StatementList} statementList
 */
var constructor = function( entityId, fingerprint, dataTypeId, statementList ) {
	if(
		typeof entityId !== 'string'
		|| fingerprint === undefined
		|| typeof dataTypeId !== 'string'
		|| statementList === undefined
	) {
		throw new Error( 'Required parameter(s) missing' );
	}

	this._id = entityId;
	this._fingerprint = fingerprint;
	this._dataTypeId = dataTypeId;
	this._statementList = statementList;
};

var SELF = wb.datamodel.Property = util.inherit( 'WbProperty', PARENT, constructor, {
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

/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
'use strict';

/**
 * Ordered list of statements each featuring the same property.
 * @constructor
 * @since 0.4
 *
 * @param {string} propertyId
 * @param {wikibase.datamodel.StatementList} statementList
 */
var SELF = wb.datamodel.StatementGroup
	= function WbDataModelStatementGroup( propertyId, statementList ) {

	if( typeof propertyId !== 'string' ) {
		throw new Error( 'propertyId needs to be a string' );
	}

	statementList = statementList || new wb.datamodel.StatementList();

	this._propertyId = propertyId;
	this.setStatementList( statementList );
};

$.extend( SELF.prototype, {
	/**
	 * @type {string}
	 */
	_propertyId: null,

	/**
	 * @type {wikibase.datamodel.StatementList}
	 */
	_statementList: null,

	/**
	 * @return {string}
	 */
	getPropertyId: function() {
		return this._propertyId;
	},

	/**
	 * @return {wikibase.datamodel.StatementList}
	 */
	getStatementList: function() {
		// Do not allow altering the encapsulated StatementList.
		return new wb.datamodel.StatementList( this._statementList.toArray() );
	},

	/**
	 * @param {wikibase.datamodel.StatementList} statementList
	 */
	setStatementList: function( statementList ) {
		var propertyIds = statementList.getPropertyIds();

		for( var i = 0; i < propertyIds.length; i++ ) {
			if( propertyIds[i] !== this._propertyId ) {
				throw new Error(
					'Mismatching property id: Expected ' + this._propertyId + ' received '
						+ propertyIds[i]
				);
			}
		}

		this._statementList = statementList;
	},

	/**
	 * @param {wikibase.datamodel.Statement} statement
	 * @return {boolean}
	 */
	hasStatement: function( statement ) {
		return this._statementList.hasStatement( statement );
	},

	/**
	 * @param {wikibase.datamodel.Statement} statement
	 */
	addStatement: function( statement ) {
		if( statement.getClaim().getMainSnak().getPropertyId() !== this._propertyId ) {
			throw new Error(
				'Mismatching property id: Expected ' + this._propertyId + ' received '
					+ statement.getClaim().getMainSnak().getPropertyId()
			);
		}
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
		return this._statementList.isEmpty();
	},

	/**
	 * @param {*} statementGroup
	 * @return {boolean}
	 */
	equals: function( statementGroup ) {
		return statementGroup === this
			|| statementGroup instanceof SELF
			&& this._propertyId === statementGroup.getPropertyId()
			&& this._statementList.equals( statementGroup.getStatementList() );
	}

} );

}( wikibase, jQuery ) );

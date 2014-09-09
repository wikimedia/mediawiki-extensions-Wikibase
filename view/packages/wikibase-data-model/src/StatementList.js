/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
'use strict';

/**
 * Ordered set of Statement objects.
 * @constructor
 * @since 0.4
 *
 * @param {wikibase.datamodel.Statement[]} statements
 */
var SELF = wb.datamodel.StatementList = function WbDataModelStatementList( statements ) {
	statements = statements || [];

	this._statements = [];
	this.length = 0;

	for( var i = 0; i < statements.length; i++ ) {
		if( !( statements[i] instanceof wb.datamodel.Statement ) ) {
			throw new Error( 'StatementList may contain Statement instances only' );
		}

		this.addStatement( statements[i] );
	}
};

$.extend( SELF.prototype, {
	/**
	 * @type {wikibase.datamodel.Statement[]}
	 */
	_statements: null,

	/**
	 * @type {number}
	 */
	length: 0,

	/**
	 * @param {wikibase.datamodel.Statement} statement
	 * @return {boolean}
	 */
	hasStatement: function( statement ) {
		for( var i = 0; i < this._statements.length; i++ ) {
			if( statement.equals( this._statements[i] ) ) {
				return true;
			}
		}
		return false;
	},

	/**
	 * @return {wikibase.datamodel.Statement[]}
	 */
	getStatements: function() {
		return this._statements;
	},

	/**
	 * @param {wikibase.datamodel.Statement} statement
	 */
	addStatement: function( statement ) {
		this._statements.push( statement );
		this.length++;
	},

	/**
	 * @return {string[]}
	 */
	getPropertyIds: function() {
		var propertyIds = [];

		for( var i = 0; i < this._statements.length; i++ ) {
			var propertyId = this._statements[i].getMainSnak().getPropertyId();
			if( $.inArray( propertyId, propertyIds ) === -1 ) {
				propertyIds.push( propertyId );
			}
		}

		return propertyIds;
	},

	/**
	 * @param {*} statementList
	 * @return {boolean}
	 */
	equals: function( statementList ) {
		if( !( statementList instanceof SELF ) ) {
			return false;
		}

		if( this.length !== statementList.length ) {
			return false;
		}

		for( var i = 0; i < this._statements.length; i++ ) {
			if( !statementList.hasStatement( this._statements[i] ) ) {
				return false;
			}
		}

		return true;
	}

} );

}( wikibase, jQuery ) );

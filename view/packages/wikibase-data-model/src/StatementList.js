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
 * @param {wikibase.datamodel.Statement[]} [statements]
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
			if(
				statement.getClaim().getGuid() === this._statements[i].getClaim().getGuid()
				&& statement.equals( this._statements[i] )
			) {
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
	 * @param {wikibase.datamodel.Statement} statement
	 */
	removeStatement: function( statement ) {
		for( var i = 0; i < this._statements.length; i++ ) {
			if(
				this._statements[i].getClaim().getGuid() === statement.getClaim().getGuid()
				&& this._statements[i].equals( statement )
			) {
				this._statements.splice( i, 1 );
				this.length--;
				return;
			}
		}
		throw new Error( 'Trying to remove a non-existing statement' );
	},

	/**
	 * @return {string[]}
	 */
	getPropertyIds: function() {
		var propertyIds = [];

		for( var i = 0; i < this._statements.length; i++ ) {
			var propertyId = this._statements[i].getClaim().getMainSnak().getPropertyId();
			if( $.inArray( propertyId, propertyIds ) === -1 ) {
				propertyIds.push( propertyId );
			}
		}

		return propertyIds;
	},

	/**
	 * @return {boolean}
	 */
	isEmpty: function() {
		return this.length === 0;
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
			if( statementList.indexOf( this._statements[i] ) !== i ) {
				return false;
			}
		}

		return true;
	},

	/**
	 * @param {wikibase.datamodel.Statement} statement
	 * @return {number}
	 */
	indexOf: function( statement ) {
		for( var i = 0; i < this._statements.length; i++ ) {
			if(
				this._statements[i].getClaim().getGuid() === statement.getClaim().getGuid()
				&& this._statements[i].equals( statement )
			) {
				return i;
			}
		}
		return -1;
	}

} );

}( wikibase, jQuery ) );

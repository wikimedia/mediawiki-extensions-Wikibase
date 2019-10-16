( function( $ ) {
'use strict';

var PARENT = require( './List.js' ),
	Statement = require( './Statement.js' );

/**
 * List of Statement objects.
 * @class StatementList
 * @extends List
 * @since 1.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {Statement[]} [statements=[]]
 */
module.exports = util.inherit(
	'WbDataModelStatementList',
	PARENT,
	function( statements ) {
		PARENT.call( this, Statement, statements );
	},
{
	/**
	 * @return {string[]}
	 */
	getPropertyIds: function() {
		var propertyIds = [];

		for( var i = 0; i < this._items.length; i++ ) {
			var propertyId = this._items[i].getClaim().getMainSnak().getPropertyId();
			if( $.inArray( propertyId, propertyIds ) === -1 ) {
				propertyIds.push( propertyId );
			}
		}

		return propertyIds;
	},

	/**
	 * @param {Statement} statement
	 * @return {string}
	 */
	getItemKey: function( statement ) {
		return statement.getClaim().getMainSnak().getPropertyId();
	}
} );

}( jQuery ) );

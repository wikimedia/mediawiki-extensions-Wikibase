( function( wb, $ ) {
'use strict';

var PARENT = wb.datamodel.List;

/**
 * List of Statement objects.
 * @class wikibase.datamodel.StatementList
 * @extends wikibase.datamodel.List
 * @since 1.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {wikibase.datamodel.Statement[]} [statements=[]]
 */
wb.datamodel.StatementList = util.inherit(
	'WbDataModelStatementList',
	PARENT,
	function( statements ) {
		PARENT.call( this, wikibase.datamodel.Statement, statements );
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
	 * @param {wikibase.datamodel.Statement} statement
	 * @return {string}
	 */
	getItemKey: function( statement ) {
		return statement.getClaim().getMainSnak().getPropertyId();
	}
} );

}( wikibase, jQuery ) );

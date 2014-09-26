/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
'use strict';

var PARENT = wb.datamodel.OrderedList;

/**
 * Ordered set of Statement objects.
 * @constructor
 * @since 0.4
 *
 * @param {wikibase.datamodel.Statement[]} [statements]
 */
wb.datamodel.StatementList = util.inherit( 'wbStatementList', PARENT, function( statements ) {
	PARENT.call( this, wikibase.datamodel.Statement, statements );
}, {
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
	}
} );

}( wikibase, jQuery ) );

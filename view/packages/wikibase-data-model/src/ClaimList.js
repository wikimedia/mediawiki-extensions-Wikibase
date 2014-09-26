/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
'use strict';

var PARENT = wb.datamodel.OrderedList;

/**
 * Ordered set of Claim objects.
 * @constructor
 * @since 0.4
 *
 * @param {wikibase.datamodel.Claim[]} [claims]
 */
wb.datamodel.ClaimList = util.inherit( 'wbClaimList', PARENT, function( claims ) {
	PARENT.call( this, wikibase.datamodel.Claim, claims );
}, {
	/**
	 * @return {string[]}
	 */
	getPropertyIds: function() {
		var propertyIds = [];

		for( var i = 0; i < this._items.length; i++ ) {
			var propertyId = this._items[i].getMainSnak().getPropertyId();
			if( $.inArray( propertyId, propertyIds ) === -1 ) {
				propertyIds.push( propertyId );
			}
		}

		return propertyIds;
	}
} );

}( wikibase, jQuery ) );

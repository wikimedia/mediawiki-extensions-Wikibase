( function( $, wb, util ) {
	'use strict';

	/**
	 * @param {wikibase.store.EntityStore} entityStore
	 */
	wb.entityIdFormatter.SimpleEntityIdHtmlFormatter = util.inherit(
		'SimpleEntityIdHtmlFormatter',
		wb.entityIdFormatter.EntityIdHtmlFormatter,
		function( entityStore ) {
			if( !entityStore || !( entityStore instanceof wb.store.EntityStore ) ) {
				throw new Error( 'Required EntityStore instance not passed' );
			}
			this._entityStore = entityStore;
		},
		{
			_entityStore: null,

			format: function( entityId ) {
				var deferred = $.Deferred();
				this._entityStore.get( entityId ).done( function( response ) {
					var res;
					if( response ) {
						res = wb.utilities.ui.buildLinkToEntityPage(
							response.getContent(),
							response.getTitle()
						);
					} else {
						res = wb.utilities.ui.buildMissingEntityInfo( entityId, entityId[0] === 'P' ? 'property' : 'item' );
					}
					deferred.resolve( $( document.createElement( 'span' ) ).html( res ).html() );
				} ).fail( function() {
					// FIXME: check fail
				} );
				return deferred.promise();
			}
		}
	);
}( jQuery, wikibase, util ) );

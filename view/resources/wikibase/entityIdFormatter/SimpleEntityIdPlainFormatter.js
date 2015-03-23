( function( $, wb, util ) {
	'use strict';

	/**
	 * @param {wikibase.store.EntityStore} entityStore
	 */
	wb.entityIdFormatter.SimpleEntityIdPlainFormatter = util.inherit(
		'SimpleEntityIdPlainFormatter',
		wb.entityIdFormatter.EntityIdPlainFormatter,
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
						res = wb.utilities.ui.buildPrettyEntityLabelText( response.getContent() );
					} else {
						res = entityId;
					}
					deferred.resolve( res );
				} ).fail( function() {
					// FIXME: check fail
				} );
				return deferred.promise();
			}

		}
	);
}( jQuery, wikibase, util ) );

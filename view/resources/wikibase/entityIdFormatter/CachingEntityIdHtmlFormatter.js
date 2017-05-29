( function ( wb, util ) {
	'use strict';

	/**
	 * @param {wikibase.entityIdFormatter.EntityIdHtmlFormatter} entityIdFormatter
	 */
	wb.entityIdFormatter.CachingEntityIdHtmlFormatter = util.inherit(
		'CachingEntityIdHtmlFormatter',
		wb.entityIdFormatter.EntityIdHtmlFormatter,
		function ( entityIdFormatter ) {
			this._entityIdFormatter = entityIdFormatter;
			this._cache = {};
		},
		{
			_entityIdFormatter: null,

			_cache: null,

			format: function ( entityId ) {
				if ( !this._cache.hasOwnProperty( entityId ) ) {
					this._cache[ entityId ] = this._entityIdFormatter.format( entityId );
				}
				return this._cache[ entityId ];
			}

		}
	);
}( wikibase, util ) );

( function ( wb, util ) {
	'use strict';

	/**
	 * @param {wikibase.entityIdFormatter.EntityIdPlainFormatter} entityIdFormatter
	 */
	wb.entityIdFormatter.CachingEntityIdPlainFormatter = util.inherit(
		'CachingEntityIdPlainFormatter',
		wb.entityIdFormatter.EntityIdPlainFormatter,
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

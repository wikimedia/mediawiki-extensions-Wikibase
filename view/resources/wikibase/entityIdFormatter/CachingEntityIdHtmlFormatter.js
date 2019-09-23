( function () {
	'use strict';

	var EntityIdHtmlFormatter = require( './EntityIdHtmlFormatter.js' );

	/**
	 * @param {EntityIdHtmlFormatter} entityIdFormatter
	 */
	module.exports = util.inherit(
		'CachingEntityIdHtmlFormatter',
		EntityIdHtmlFormatter,
		function ( entityIdFormatter ) {
			this._entityIdFormatter = entityIdFormatter;
			this._cache = {};
		},
		{
			_entityIdFormatter: null,

			_cache: null,

			format: function ( entityId ) {
				if ( !Object.prototype.hasOwnProperty.call( this._cache, entityId ) ) {
					this._cache[ entityId ] = this._entityIdFormatter.format( entityId );
				}
				return this._cache[ entityId ];
			}

		}
	);
}() );

( function () {
	'use strict';

	var EntityIdPlainFormatter = require( './EntityIdPlainFormatter.js' );

	/**
	 * @param {EntityIdPlainFormatter} entityIdFormatter
	 */
	module.exports = util.inherit(
		'CachingEntityIdPlainFormatter',
		EntityIdPlainFormatter,
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

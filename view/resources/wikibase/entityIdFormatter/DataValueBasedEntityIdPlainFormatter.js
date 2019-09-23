( function () {
	'use strict';

	var EntityIdPlainFormatter = require( './EntityIdPlainFormatter.js' );

	/**
	 * @param {dataValues.ValueParser} parser
	 * @param {dataValues.ValueFormatter} formatter
	 */
	module.exports = util.inherit(
		'DataValueBasedEntityIdPlainFormatter',
		EntityIdPlainFormatter,
		function ( parser, formatter ) {
			this._parser = parser;
			this._formatter = formatter;
		},
		{
			_parser: null,

			_formatter: null,

			format: function ( entityId ) {
				var deferred = $.Deferred(),
					self = this;
				this._parser.parse( entityId ).done( function ( parsed ) {
					return self._formatter.format( parsed ).done( function ( response ) {
						deferred.resolve( response );
					} ).fail( function () {
						deferred.resolve( entityId );
					} );
				} ).fail( function () {
					deferred.resolve( entityId );
				} );

				return deferred.promise();
			}

		}
	);
}() );

( function () {
	'use strict';

	var EntityIdHtmlFormatter = require( './EntityIdHtmlFormatter.js' );

	/**
	 * @param {dataValues.ValueParser} parser
	 * @param {dataValues.ValueFormatter} formatter
	 */
	module.exports = util.inherit(
		'DataValueBasedEntityIdHtmlFormatter',
		EntityIdHtmlFormatter,
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
						deferred.resolve( mw.html.escape( entityId ) );
					} );
				} ).fail( function () {
					deferred.resolve( mw.html.escape( entityId ) );
				} );

				return deferred.promise();
			}

		}
	);
}() );

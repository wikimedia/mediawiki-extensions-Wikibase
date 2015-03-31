( function( $, wb, util ) {
	'use strict';

	/**
	 * @param {dataValues.ValueParser} parser
	 * @param {dataValues.ValueFormatter} formatter
	 */
	wb.entityIdFormatter.DataValueBasedEntityIdHtmlFormatter = util.inherit(
		'DataValueBasedEntityIdHtmlFormatter',
		wb.entityIdFormatter.EntityIdHtmlFormatter,
		function( parser, formatter ) {
			this._parser = parser;
			this._formatter = formatter;
		},
		{
			_parser: null,

			_formatter: null,

			format: function( entityId ) {
				var deferred = $.Deferred(),
					self = this;
				this._parser.parse( entityId ).done( function( parsed ) {
					return self._formatter.format( parsed, null, 'text/html' ).done( function( response ) {
						deferred.resolve( response );
					} ).fail( function() {
						deferred.resolve( entityId ); // FIXME: escape
					} );
				} ).fail( function() {
					deferred.resolve( entityId ); // FIXME: escape
				} );

				return deferred.promise();
			}

		}
	);
}( jQuery, wikibase, util ) );

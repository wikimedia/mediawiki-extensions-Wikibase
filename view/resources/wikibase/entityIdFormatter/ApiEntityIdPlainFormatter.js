( function( $, wb, util ) {
	'use strict';

	/**
	 * @param {wikibase.api.RepoApi} repoApi
	 */
	wb.entityIdFormatter.ApiEntityIdPlainFormatter = util.inherit(
		'ApiEntityIdPlainFormatter',
		wb.entityIdFormatter.EntityIdPlainFormatter,
		function( repoApi ) {
			if( !repoApi || !( repoApi instanceof wb.api.RepoApi ) ) {
				throw new Error( 'Required RepoApi instance not passed' );
			}
			this._repoApi = repoApi;
		},
		{
			_repoApi: null,

			format: function( entityId ) {
				var deferred = $.Deferred(),
					self = this;
				this._repoApi.parseValue( 'wikibase-entityid', [ entityId ] ).done( function( apiResponse ) {
					return self._repoApi.formatValue( apiResponse.results[0], null, null, 'text/plain' ).done( function( response ) {
						deferred.resolve( response.result );
					} ).fail( function() {
						deferred.resolve( entityId );
					} );
				} ).fail( function() {
					deferred.resolve( entityId );
				} );

				return deferred.promise();
			}

		}
	);
}( jQuery, wikibase, util ) );

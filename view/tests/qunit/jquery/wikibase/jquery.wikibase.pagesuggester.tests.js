/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( $, QUnit ) {
	'use strict';

	/**
	 * Factory creating a jQuery.wikibase.pagesuggester widget suitable for testing.
	 *
	 * @param {Object} [options]
	 * @return {jQuery}
	 */
	var newTestPageSuggester = function ( options ) {
		return $( '<input />' )
			.addClass( 'test_pagesuggester' )
			.appendTo( 'body' )
			.pagesuggester( options );
	};

	QUnit.module( 'jquery.wikibase.pagesuggester', {
		teardown: function () {
			var $pageSuggester = $( '.test_pagesuggester' ),
				pageSuggester = $pageSuggester.data( 'pagesuggester' );
			if ( pageSuggester ) {
				pageSuggester.destroy();
			}
			$pageSuggester.remove();
		}
	} );

	QUnit.test( 'Create', function ( assert ) {
		assert.expect( 1 );
		var $pageSuggester = newTestPageSuggester();

		assert.ok(
			$pageSuggester.data( 'pagesuggester' ) instanceof $.wikibase.pagesuggester,
			'Instantiated page suggester.'
		);
	} );

	QUnit.test( 'Try searching for suggestions without a site', function ( assert ) {
		var $pageSuggester = newTestPageSuggester(),
			pageSuggester = $pageSuggester.data( 'pagesuggester' );

		var done = assert.async();

		pageSuggester.search()
		.done( function () {
			assert.ok(
				false,
				'Searching successful although supposed to fail.'
			);
		} )
		.fail( function () {
			assert.ok(
				true,
				'Searching failed as expected.'
			);
		} )
		.always( done );

	} );

}( jQuery, QUnit ) );

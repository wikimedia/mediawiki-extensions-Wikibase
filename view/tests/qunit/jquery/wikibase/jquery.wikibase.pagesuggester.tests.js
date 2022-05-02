/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function () {
	'use strict';

	/**
	 * Factory creating a jQuery.wikibase.pagesuggester widget suitable for testing.
	 *
	 * @param {Object} [options]
	 * @return {jQuery}
	 */
	var newTestPageSuggester = function ( options ) {
		return $( '<input>' )
			.addClass( 'test_pagesuggester' )
			.appendTo( document.body )
			.pagesuggester( options );
	};

	QUnit.module( 'jquery.wikibase.pagesuggester', {
		afterEach: function () {
			var $pageSuggester = $( '.test_pagesuggester' ),
				pageSuggester = $pageSuggester.data( 'pagesuggester' );
			if ( pageSuggester ) {
				pageSuggester.destroy();
			}
			$pageSuggester.remove();
		}
	} );

	QUnit.test( 'Create', function ( assert ) {
		var $pageSuggester = newTestPageSuggester();

		assert.true(
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
			assert.true(
				false,
				'Searching successful although supposed to fail.'
			);
		} )
		.fail( function () {
			assert.true(
				true,
				'Searching failed as expected.'
			);
		} )
		.always( done );

	} );

}() );

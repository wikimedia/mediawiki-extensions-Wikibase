/**
 * QUnit tests for entity selector jQuery widget
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( $, QUnit, undefined ) {
	'use strict';

	/**
	 * Factory for creating a jquery.wikibase.entityselector widget suitable for testing.
	 *
	 * @param {Object} customOptions
	 */
	var newTestEntitySelector = function( customOptions ) {
		var options = {
			url: 'url'
		};
		if ( options ) {
			$.extend( options, customOptions );
		}
		// element needs to be in the DOM for setting text selection range
		return $( '<input/>' ).addClass( 'test_entityselector').appendTo( 'body' ).entityselector( options );
	};

	QUnit.module( 'jquery.wikibase.entityselector', QUnit.newMwEnvironment( {
		teardown: function() {
			$( '.test_entityselector' ).remove();
		}
	} ) );

	QUnit.test( 'Basic tests', function( assert ) {
		var input = newTestEntitySelector();
		var entityselector = input.data( 'entityselector' );
		var exampleResponse = {
			searchinfo: {
				totalhits: 3,
				search: 'ab'
			},
			search: [
				{
					id: 1,
					label: 'abc',
					description: 'description',
					aliases: ['ac', 'def'],
					score: 1
				},
				{
					id: 2,
					label: 'x',
					aliases: ['yz'],
					score: 0.5
				},
				{
					id: 3,
					label: 'g',
					score: 0.2
				}
			],
			success: 1
		};

		input.on( 'entityselectorresponse', $.proxy( function( event, items ) {
			assert.deepEqual(
				items,
				exampleResponse.search,
				'Fired response event passing result set.'
			);
		}, this ) );
		input.val( 'ab' );
		entityselector._success( exampleResponse );
		input.off( 'entityselectorresponse' );

		assert.equal(
			input.val(),
			'abc',
			'Replaced input element\'s value with first result (remaining part of the string is highlighted).'
		);

		assert.equal(
			entityselector.menu.element.children().first().find( 'span' ).length,
			4,
			'Created suggestion list section\'s DOM structure.'
		);

		assert.equal(
			$( entityselector.menu.element.children()[1] ).find( 'span' ).length,
			3,
			'Created suggestion list section\'s DOM structure - skipping description when empty.'
		);

		assert.equal(
			$( entityselector.menu.element.children()[2] ).find( 'span' ).length,
			2,
			'Created suggestion list section\'s DOM structure - skipping description and aliases when empty.'
		);

		entityselector.close();

		entityselector.destroy();
		input.remove();

		exampleResponse['search-continue'] = 4;

		input = newTestEntitySelector( { limit: 1 } );
		entityselector = input.data( 'entityselector' );

		input.val( 'ab' );
		entityselector._success( exampleResponse );

		assert.equal(
			entityselector.menu.element.children( 'li' ).length,
			( exampleResponse.search.length + 1 ),
			'Appended "more" link.'
		);

		entityselector.offset = 0;
		input.val( 'ab' );
		entityselector._success( exampleResponse );

		assert.equal(
			entityselector.menu.element.children( 'li' ).length,
			( exampleResponse.search.length + 1 ),
			'Cleared result cache after resetting the offset.'
		);

	} );

	QUnit.test( 'Auto-complete alias instead of label', function( assert ) {
		var input = newTestEntitySelector();
		var entityselector = input.data( 'entityselector' );
		var exampleResponse = {
			searchinfo: {
				totalhits: 1,
				search: 'ab'
			},
			search: [
				{
					id: 1,
					label: 'xyz',
					aliases: ['abc'],
					score: 1
				}
			],
			success: 1
		};

		input.val( 'ab' );
		entityselector._success( exampleResponse );

		assert.equal(
			input.val(),
			'abc',
			'Auto-completed alias instead of label.'
		);

		input.remove();
	} );

}( jQuery, QUnit ) );

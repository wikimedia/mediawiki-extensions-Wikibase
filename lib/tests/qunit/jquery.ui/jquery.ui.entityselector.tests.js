/**
 * QUnit tests for entity selector jQuery widget
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( $, QUnit, undefined ) {
	'use strict';

	/**
	 * Factory for creating a jquery.ui.entityselector widget suitable for testing.
	 *
	 * @param {Object} options
	 */
	var newTestEntitySelector = function( options ) {
		options = options || {
			url: 'url',
			language: 'language'
		};
		return $( '<input/>' ).entityselector( options );
	};

	/**
	 * Creates an example API response.
	 *
	 * @return {Object}
	 */
	var createResponse = function() {
		return {
			searchinfo: {
				totalhits: 2,
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
					aliases: ['y'],
					score: 0.5
				}
			],
			success: 1
		};
	};

	QUnit.module( 'jquery.ui.entityselector', QUnit.newMwEnvironment() );

	QUnit.test( 'basic tests', function( assert ) {
		var input = newTestEntitySelector();
		var entityselector = input.data( 'entityselector' );
		var exampleResponse = createResponse();

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
			3,
			'Created suggestion list section\'s DOM structure.'
		);

		entityselector.close();

		entityselector.destroy();
		input.remove();
	} );

}( jQuery, QUnit ) );

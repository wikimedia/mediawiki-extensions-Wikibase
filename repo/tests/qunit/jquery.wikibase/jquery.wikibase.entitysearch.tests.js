/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function () {
	'use strict';

	QUnit.module( 'jquery.wikibase.entitysearch' );

	require( '../../../resources/jquery.wikibase/jquery.wikibase.entitysearch.js' );

	/**
	 * Entity stubs as returned from the API and handled by the entityselector.
	 *
	 * @type {Object[]}
	 */
	var entityStubs = [
		{
			id: 1,
			label: 'abc',
			description: 'description',
			aliases: [ 'ac', 'def' ]
		},
		{
			id: 2,
			label: 'x',
			aliases: [ 'yz' ]
		},
		{
			id: 3,
			label: 'g'
		}
	];

	/**
	 * @param {Object} [options]
	 * @return {jQuery}
	 */
	var newEntitysearch = function ( options ) {
		options = $.extend( {
			source: entityStubs
		}, options || {} );

		return $( '<input>' )
			.addClass( 'test-entitysearch' )
			.entitysearch( options );
	};

	QUnit.test( 'Create & destroy', function ( assert ) {
		var $entitysearch = newEntitysearch();

		assert.true(
			$entitysearch.data( 'entitysearch' ) instanceof $.wikibase.entitysearch,
			'Instantiated widget.'
		);

		$entitysearch.data( 'entitysearch' ).destroy();

		assert.strictEqual(
			$entitysearch.data( 'entitysearch' ),
			undefined,
			'Destroyed widget.'
		);
	} );

}() );

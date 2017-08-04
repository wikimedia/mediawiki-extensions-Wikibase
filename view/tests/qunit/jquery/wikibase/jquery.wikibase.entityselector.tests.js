/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( $, QUnit ) {
	'use strict';

	/**
	 * Entity stubs as returned from the API and handled by the entityselector.
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
	 * Factory creating a jQuery.wikibase.entityselector widget suitable for testing.
	 *
	 * @param {Object} [options]
	 * @return {jQuery}
	 */
	var newTestEntitySelector = function ( options ) {
		options = $.extend( {
			source: entityStubs,
			delay: 0 // Time waster, also some tests below assume this to be < 100ms
		}, options || {} );

		return $( '<input />' )
			.addClass( 'test-entityselector' )
			.appendTo( 'body' )
			.entityselector( options );
	};

	QUnit.module( 'jquery.wikibase.entityselector', QUnit.newMwEnvironment( {
		teardown: function () {
			$( '.test-entityselector' ).remove();
		}
	} ) );

	QUnit.test( 'Create', function ( assert ) {
		assert.expect( 1 );
		var $entitySelector = newTestEntitySelector();

		assert.ok(
			$entitySelector.data( 'entityselector' ) instanceof $.wikibase.entityselector,
			'Instantiated entityselector.'
		);
	} );

	QUnit.test( 'Implicitly select entity by matching label / selectedEntity()', function ( assert ) {
		var $entitySelector = newTestEntitySelector(),
			entitySelector = $entitySelector.data( 'entityselector' ),
			done = assert.async();
		$entitySelector.val( 'abc' );

		$entitySelector
		.one( 'entityselectorselected', function ( event, id ) {
			assert.deepEqual(
				id,
				entityStubs[ 0 ].id,
				'Selected first entity.'
			);

			assert.deepEqual(
				entitySelector.selectedEntity(),
				entityStubs[ 0 ],
				'Verified selected entity using selectedEntity().'
			);

			done();
		} );

		$entitySelector.trigger( 'eachchange.entityselector' );
	} );

	QUnit.test( 'Indicate unrecognized input', function ( assert ) {
		var $entitySelector = newTestEntitySelector();
		$entitySelector.data( 'entityselector' );

		assert.notOk( $entitySelector.hasClass( 'ui-entityselector-input-unrecognized' ) );

		$entitySelector.val( 'does-not-exist' );
		$entitySelector.blur();
		assert.ok( $entitySelector.hasClass( 'ui-entityselector-input-unrecognized' ) );
	} );

	QUnit.test( 'Indicate recognized input', function ( assert ) {
		var done = assert.async(),
			$entitySelector = newTestEntitySelector();

		$entitySelector.data( 'entityselector' );

		assert.notOk( $entitySelector.hasClass( 'ui-entityselector-input-recognized' ) );
		$entitySelector.val( 'abc' );

		$entitySelector
			.one( 'entityselectorselected', function ( event, id ) {
				$entitySelector.blur();
				assert.ok( $entitySelector.hasClass( 'ui-entityselector-input-recognized' ) );
				done();
			} );

		$entitySelector.trigger( 'eachchange.entityselector' );
	} );

	QUnit.test( 'Don\'t implicitly select entity by matching alias / selectedEntity()', function ( assert ) {
		assert.expect( 0 );

		var $entitySelector = newTestEntitySelector(),
			done = assert.async();

		$entitySelector.val( 'yz' );

		$entitySelector
		.one( 'entityselectorselected', function ( event, id ) {
			assert.ok( false, 'entity should not automatically be selected based on the alias' );
		} );

		$entitySelector.trigger( 'eachchange.entityselector' );

		window.setTimeout( done, 100 );
	} );

	QUnit.test( 'Item constructor', function ( assert ) {
		assert.expect( 2 );
		var item = new $.wikibase.entityselector.Item( 'label', 'value', entityStubs[ 0 ] );

		assert.ok(
			item instanceof $.wikibase.entityselector.Item,
			'Instantiated default entityselector item.'
		);

		assert.throws(
			function () {
				item = new $.wikibase.entityselector.Item( 'label', 'value' );
			},
			'Throwing error when omitting entity stub on instantiation.'
		);
	} );

	QUnit.test( 'Cache invalidation of small (not continued) search results', function ( assert ) {
		assert.expect( 2 );

		var $entitySelector = newTestEntitySelector( {
				source: function () {
					return $.Deferred().resolve( [ 'Alpha' ] ).promise();
				}
			} ),
			entitySelector = $entitySelector.data( 'entityselector' );

		return entitySelector._getSuggestions().then( function ( suggestions ) {
			assert.deepEqual( suggestions, [ 'Alpha' ], 'should cache' );

			return entitySelector._getSuggestions();
		} ).then( function ( suggestions, term ) {
			assert.deepEqual( suggestions, [ 'Alpha' ], 'should not concat on existing cache' );
		} );
	} );

}( jQuery, QUnit ) );

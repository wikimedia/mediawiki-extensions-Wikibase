/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, QUnit ) {
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
			aliases: ['ac', 'def']
		},
		{
			id: 2,
			label: 'x',
			aliases: ['yz']
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
	var newTestEntitySelector = function( options ) {
		options = $.extend( {
			source: entityStubs
		}, options || {} );

		return $( '<input />' )
			.addClass( 'test-entityselector')
			.appendTo( 'body' )
			.entityselector( options );
	};

	QUnit.module( 'jquery.wikibase.entityselector', QUnit.newMwEnvironment( {
		teardown: function() {
			$( '.test-entityselector' ).remove();
		}
	} ) );

	QUnit.test( 'Create', function( assert ) {
		var $entitySelector = newTestEntitySelector();

		assert.ok(
			$entitySelector.data( 'entityselector' ) instanceof $.wikibase.entityselector,
			'Instantiated entityselector.'
		);
	} );

	QUnit.test( 'Implicitly select entity by matching label / selectedEntity()', 2, function( assert ) {
		var $entitySelector = newTestEntitySelector(),
			entitySelector = $entitySelector.data( 'entityselector' );

		$entitySelector.val( 'abc' );

		$entitySelector
		.one( 'entityselectorselected', function( event, id ) {
			assert.deepEqual(
				id,
				entityStubs[0].id,
				'Selected first entity.'
			);

			assert.deepEqual(
				entitySelector.selectedEntity(),
				entityStubs[0],
				'Verified selected entity using selectedEntity().'
			);

			QUnit.start();
		} );

		QUnit.stop();

		$entitySelector.trigger( 'eachchange.entityselector' );
	} );

	QUnit.test( 'Don\'t implicitly select entity by matching alias / selectedEntity()', function( assert ) {
		QUnit.expect( 0 );

		var $entitySelector = newTestEntitySelector();

		$entitySelector.val( 'yz' );

		$entitySelector
		.one( 'entityselectorselected', function( event, id ) {
			assert.ok( false, 'entity should not automatically be selected based on the alias' );
		} );

		QUnit.stop();

		$entitySelector.trigger( 'eachchange.entityselector' );

		window.setTimeout( function() {
			QUnit.start();
		}, 200 );
	} );

	QUnit.test( 'Item constructor', function( assert ) {
		var item = new $.wikibase.entityselector.Item( 'label', 'value', entityStubs[0] );

		assert.ok(
			item instanceof $.wikibase.entityselector.Item,
			'Instantiated default entityselector item.'
		);

		assert.throws(
			function() {
				item = new $.wikibase.entityselector.Item( 'label', 'value' );
			},
			'Throwing error when omitting entity stub on instantiation.'
		);
	} );

}( jQuery, QUnit ) );

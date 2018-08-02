/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( $, QUnit, sinon, mw ) {
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

	QUnit.test( 'When fireSearchHook is called with term', function ( assert ) {
		var hookStub = sinon.stub( mw, 'hook' ),
			fireSpy = sinon.spy(),
			hook = 'HOOK_NAME',
			term = '[TERM]',
			$entitySelector = newTestEntitySelector( { searchHookName: hook } ),
			entitySelector = $entitySelector.data( 'entityselector' );

		hookStub.withArgs( hook ).returns( { fire: fireSpy } );
		entitySelector._fireSearchHook( term );

		assert.equal( fireSpy.getCall( 0 ).args[ 0 ].term, term, 'Then mw.hook().fire() is called with term' );

		hookStub.restore();
	} );

	QUnit.test( 'When fireSearchHook is called and a promise is added to the list', function ( assert ) {
		var hookStub = sinon.stub( mw, 'hook' ),
			hook = 'HOOK_NAME',
			promise = '[PROMISE]',
			$entitySelector = newTestEntitySelector( { searchHookName: hook } ),
			entitySelector = $entitySelector.data( 'entityselector' );

		hookStub.withArgs( hook ).returns( {
			fire: function ( data, addPromise ) {
				addPromise( promise );
			}
		} );

		assert.deepEqual( entitySelector._fireSearchHook(), [ promise ], 'Then the list returned should contain the promise' );

		hookStub.restore();
	} );

	QUnit.test( 'When combineResults is called with promised item list', function ( assert ) {
		var done = assert.async(),
			itemList = [ { id: '[ID]' } ],
			$entitySelector = newTestEntitySelector(),
			entitySelector = $entitySelector.data( 'entityselector' );

		entitySelector._combineResults( [ $.Deferred().resolve( itemList ).promise() ] ).then( function ( list ) {
			assert.deepEqual(
				list,
				itemList,
				'Then item list is returned'
			);
			done();
		} );
	} );

	QUnit.test( 'When combineResults is called with multiple promised item lists', function ( assert ) {
		var done = assert.async(),
			itemList = [ { id: '[ID]' } ],
			itemList1 = [ { id: '[ID1]' } ],
			itemList2 = [ { id: '[ID2]' } ],
			$entitySelector = newTestEntitySelector(),
			entitySelector = $entitySelector.data( 'entityselector' );

		entitySelector._combineResults( [
			$.Deferred().resolve( itemList1 ).promise(),
			$.Deferred().resolve( itemList2 ).promise()
		],
		itemList ).then( function ( list ) {
			assert.deepEqual(
				list,
				itemList2.concat( itemList1 ).concat( itemList ),
				'Then all items are added to the front of the list that is returned'
			);
			done();
		} );
	} );

	QUnit.test( 'When promised item list contains ID that is already in the list', function ( assert ) {
		var done = assert.async(),
			itemList = [ { id: '[ID]', data: '[OLD]' } ],
			itemListConsumer = [ { id: '[ID]', data: '[NEW]' } ],
			$entitySelector = newTestEntitySelector(),
			entitySelector = $entitySelector.data( 'entityselector' );

		entitySelector._combineResults( [
			$.Deferred().resolve( itemListConsumer ).promise() ],
		itemList
		).then( function ( list ) {
			assert.deepEqual(
				list,
				itemListConsumer,
				'Then items with the same ID are overwritten'
			);
			done();
		} );
	} );

	QUnit.test( 'When element focus method is called and value is empty', function ( assert ) {
		var hookStub = sinon.stub( mw, 'hook' ),
			fireSpy = sinon.spy(),
			hook = 'HOOK_NAME',
			value = '',
			$entitySelector = newTestEntitySelector( { searchHookName: hook } ),
			entitySelector = $entitySelector.data( 'entityselector' );

		hookStub.withArgs( hook ).returns( { fire: fireSpy } );

		$entitySelector.val( value );
		entitySelector.element.focus();

		assert.equal( fireSpy.getCall( 0 ).args[ 0 ].term, value, 'Then mw.hook().fire() is called with empty value' );

		hookStub.restore();
	} );

	QUnit.test( 'When element focus method is called and value is NOT empty', function ( assert ) {
		var hookStub = sinon.stub( mw, 'hook' ),
			fireSpy = sinon.spy(),
			hook = 'HOOK_NAME',
			value = '[NOT_EMPTY]',
			$entitySelector = newTestEntitySelector( { searchHookName: hook } ),
			entitySelector = $entitySelector.data( 'entityselector' );

		hookStub.withArgs( hook ).returns( { fire: fireSpy } );

		$entitySelector.val( value );
		entitySelector.element.focus();

		assert.equal( fireSpy.getCall( 0 ), null, 'Then mw.hook().fire() is NOT called' );

		hookStub.restore();
	} );

}( jQuery, QUnit, sinon, mediaWiki ) );

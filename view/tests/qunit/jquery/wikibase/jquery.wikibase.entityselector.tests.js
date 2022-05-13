/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function () {
	'use strict';

	/**
	 * Entity stubs as returned from the API and handled by the entityselector.
	 *
	 * @type {Object[]}
	 */
	var entityStubs = [
		{
			id: 1,
			display: {
				label: { value: 'abc', language: 'en' },
				description: { value: 'description', language: 'en' }
			},
			label: 'abc',
			description: 'description',
			aliases: [ 'ac', 'def' ]
		},
		{
			id: 2,
			display: {
				label: { value: 'x', language: 'en' }
			},
			label: 'x',
			aliases: [ 'yz' ]
		},
		{
			id: 3,
			display: {
				label: { value: 'g', language: 'en' }
			},
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

		return $( '<input>' )
			.addClass( 'test-entityselector' )
			.appendTo( document.body )
			.entityselector( options );
	};

	QUnit.module( 'jquery.wikibase.entityselector', QUnit.newMwEnvironment( {
		afterEach: function () {
			$( '.test-entityselector' ).remove();
		}
	} ) );

	QUnit.test( 'Create', function ( assert ) {
		var $entitySelector = newTestEntitySelector();

		assert.true(
			$entitySelector.data( 'entityselector' ) instanceof $.wikibase.entityselector,
			'Instantiated entityselector.'
		);
	} );

	QUnit.test( 'Test request parameters that must be sent to the API', function ( assert ) {
		var mockTerm = 'someTerm',
			mockLanguage = 'someLanguage',
			$entitySelector = newTestEntitySelector( { language: mockLanguage } ),
			entitySelector = $entitySelector.data( 'entityselector' ),
			expectedParameters = {
				action: 'wbsearchentities',
				search: mockTerm,
				format: 'json',
				language: mockLanguage,
				uselang: mockLanguage,
				type: 'item',
				errorformat: 'plaintext'
			};

		assert.deepEqual( entitySelector._getSearchApiParameters( mockTerm ), expectedParameters );
	} );

	QUnit.test( 'Indicate unrecognized input', function ( assert ) {
		var $entitySelector = newTestEntitySelector();
		$entitySelector.data( 'entityselector' );

		assert.false( $entitySelector.hasClass( 'ui-entityselector-input-unrecognized' ) );

		$entitySelector.val( 'does-not-exist' );
		$entitySelector.trigger( 'blur' );
		assert.true( $entitySelector.hasClass( 'ui-entityselector-input-unrecognized' ) );
	} );

	QUnit.test( 'Indicate recognized input', function ( assert ) {
		var $entitySelector = newTestEntitySelector();

		var entitySelector = $entitySelector.data( 'entityselector' );

		assert.false( $entitySelector.hasClass( 'ui-entityselector-input-recognized' ) );
		entitySelector.selectedEntity( 'abc' );

		$entitySelector.trigger( 'blur' );
		assert.true( $entitySelector.hasClass( 'ui-entityselector-input-recognized' ) );
	} );

	QUnit.test( 'Item constructor', function ( assert ) {
		var item = new $.wikibase.entityselector.Item( 'label', 'value', entityStubs[ 0 ] );

		assert.true(
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

		assert.strictEqual( fireSpy.getCall( 0 ).args[ 0 ].term, term, 'Then mw.hook().fire() is called with term' );

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

		assert.deepEqual( entitySelector._fireSearchHook( '[TERM]' ), [ promise ], 'Then the list returned should contain the promise' );

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

	QUnit.test( 'When showDefaultSuggestions() is called and value is empty', function ( assert ) {
		var done = assert.async(),
			hookStub = sinon.stub( mw, 'hook' ),
			hook = 'HOOK_NAME',
			emptyValue = '',
			suggestions = [ {
				id: '[ID]',
				display: {
					label: { value: '[LABEL]', language: 'en' },
					description: { value: '[DESCRIPTION]', language: 'en' }
				},
				label: '[LABEL]',
				description: '[DESCRIPTION]'
			} ],
			promise = $.Deferred().resolve( suggestions ).promise(),
			$entitySelector = newTestEntitySelector( { searchHookName: hook } ),
			entitySelector = $entitySelector.data( 'entityselector' ),
			combineStub = sinon.stub( entitySelector, '_combineResults' ),
			updateMenuSpy = sinon.spy( entitySelector, '_updateMenu' );

		hookStub.withArgs( hook ).returns( { fire: function ( d, addPromise ) {
			addPromise( promise );
		} } );
		combineStub.returns( { then: function ( callback ) {
			callback( suggestions );
		} } );

		$entitySelector.val( emptyValue );
		entitySelector._showDefaultSuggestions();

		assert.deepEqual( updateMenuSpy.getCall( 0 ).args[ 0 ], suggestions, 'Then _updateMenu is called with suggestions' );

		hookStub.restore();
		combineStub.restore();
		entitySelector._updateMenu.restore();
		done();
	} );

	QUnit.test( 'When showDefaultSuggestions() is called and value is NOT empty', function ( assert ) {
		var hookStub = sinon.stub( mw, 'hook' ),
			fireSpy = sinon.spy(),
			hook = 'HOOK_NAME',
			value = '[NOT_EMPTY]',
			$entitySelector = newTestEntitySelector( { searchHookName: hook } ),
			entitySelector = $entitySelector.data( 'entityselector' );

		hookStub.withArgs( hook ).returns( { fire: fireSpy } );

		$entitySelector.val( value );
		entitySelector._showDefaultSuggestions();

		assert.strictEqual( fireSpy.getCall( 0 ), null, 'Then mw.hook().fire() is NOT called' );
		hookStub.restore();
	} );

	QUnit.test( 'When _combineResults() is called and all items have a rating', function ( assert ) {
		var done = assert.async(),
			itemList1 = [ { id: '[ID4]', rating: 4 }, { id: '[ID1]', rating: 1 } ],
			itemList2 = [ { id: '[ID5]', rating: 5 }, { id: '[ID3]', rating: 3 }, { id: '[ID2]', rating: 2 } ],
			sortedItemList = [ { id: '[ID5]', rating: 5 }, { id: '[ID4]', rating: 4 }, { id: '[ID3]', rating: 3 },
				{ id: '[ID2]', rating: 2 }, { id: '[ID1]', rating: 1 } ],
			promise1 = $.Deferred().resolve( itemList1 ).promise(),
			promise2 = $.Deferred().resolve( itemList2 ).promise(),
			$entitySelector = newTestEntitySelector(),
			entitySelector = $entitySelector.data( 'entityselector' );

		entitySelector._combineResults( [ promise1, promise2 ] ).then( function ( list ) {
			assert.deepEqual(
				list,
				sortedItemList,
				'Then item list is returned is sorted  by rating desc'
			);
			done();
		} );
	} );

	QUnit.test( 'When _combineResults() is called and all items have the same rating', function ( assert ) {
		// note: this test assumes that Array.sort() is stable,
		// which is currently the case in all major browsers, but not guaranteed by the spec
		var done = assert.async(),
			itemList = [ { id: '[ID1]', rating: 1 }, { id: '[ID2]', rating: 1 }, { id: '[ID3]', rating: 1 } ],
			promise = $.Deferred().resolve( itemList ).promise(),
			$entitySelector = newTestEntitySelector(),
			entitySelector = $entitySelector.data( 'entityselector' );

		entitySelector._combineResults( [ promise ] ).then( function ( list ) {
			assert.deepEqual(
				list,
				itemList,
				'Then item list is returned has the original order'
			);
			done();
		} );
	} );

	QUnit.test( 'When _combineResults() is called and all items are missing a rating', function ( assert ) {
		// note: this test assumes that Array.sort() is stable,
		// which is currently the case in all major browsers, but not guaranteed by the spec
		var done = assert.async(),
			itemList = [ { id: '[ID1]' }, { id: '[ID2]' }, { id: '[ID3]' } ],
			promise = $.Deferred().resolve( itemList ).promise(),
			$entitySelector = newTestEntitySelector(),
			entitySelector = $entitySelector.data( 'entityselector' );

		entitySelector._combineResults( [ promise ] ).then( function ( list ) {
			assert.deepEqual(
				list,
				itemList,
				'Then item list is returned has the original order'
			);
			done();
		} );
	} );

	QUnit.test( 'When _combineResults() is called and some items have an equal rating or none', function ( assert ) {
		var done = assert.async(),
			itemList1 = [ { id: '[ID4]', rating: 4 }, { id: '[ID1]' } ],
			itemList2 = [ { id: '[ID5]', rating: 5 }, { id: '[ID3]', rating: 2.5 }, { id: '[ID2]', rating: 2.5 } ],
			sortedItemList = [ { id: '[ID5]', rating: 5 }, { id: '[ID4]', rating: 4 }, { id: '[ID3]', rating: 2.5 },
				{ id: '[ID2]', rating: 2.5 }, { id: '[ID1]' } ],
			promise1 = $.Deferred().resolve( itemList1 ).promise(),
			promise2 = $.Deferred().resolve( itemList2 ).promise(),
			$entitySelector = newTestEntitySelector(),
			entitySelector = $entitySelector.data( 'entityselector' );

		entitySelector._combineResults( [ promise1, promise2 ] ).then( function ( list ) {
			assert.deepEqual(
				list,
				sortedItemList,
				'Then item list is returned is sorted  by rating desc'
			);
			done();
		} );
	} );

	QUnit.test( 'When _combineResults() is called with hook results and default results', function ( assert ) {
		var done = assert.async(),
			itemList = [ { id: '[ID1]' }, { id: '[ID2]' }, { id: '[ID3]' } ],
			defaultList = [ { id: '[IDX]', rating: 5 } ],
			expectedList = itemList.concat( defaultList ),
			promise = $.Deferred().resolve( itemList ).promise(),
			$entitySelector = newTestEntitySelector(),
			entitySelector = $entitySelector.data( 'entityselector' );

		entitySelector._combineResults( [ promise ], defaultList ).then( function ( list ) {
			assert.deepEqual(
				list,
				expectedList,
				'Then hook results are always on top of the list even if default results have a higher ranking'
			);
			done();
		} );
	} );

	QUnit.test( 'Applies api parameter overrides from hook', function ( assert ) {
		var result,
			hookStub = sinon.stub( mw, 'hook' ),
			fireSpy = sinon.spy(),
			hook = 'HOOK_NAME',
			term = '[TERM]',
			$entitySelector = newTestEntitySelector( { searchApiParametersHookName: hook } ),
			entitySelector = $entitySelector.data( 'entityselector' );

		hookStub.withArgs( hook ).returns( { fire: fireSpy } );
		result = entitySelector._getSearchApiParameters( term );
		assert.strictEqual( fireSpy.getCall( 0 ).args[ 0 ], result, 'Then mw.hook().fire() is called with final result' );

		hookStub.restore();
	} );

	QUnit.test( 'Error message defaults to parsing by function', function ( assert ) {
		var $entitySelector = newTestEntitySelector();

		assert.strictEqual(
			typeof $entitySelector.data( 'entityselector' ).options.messages.error,
			'function',
			'Default error message is parsed from exception'
		);
	} );

	QUnit.test( 'Error parser gets exception message from exception object', function ( assert ) {
		var $entitySelector = newTestEntitySelector();

		$entitySelector.data( 'entityselector' )._error = {
			name: 'Error',
			detailedMessage: 'message',
			message: '',
			code: 0,
			action: 'abc'
		};

		var errorMessage = $entitySelector.data( 'entityselector' ).options.messages.error();

		assert.strictEqual(
			errorMessage,
			'message',
			'Default error message is fetched from actual error'
		);
	} );
}() );

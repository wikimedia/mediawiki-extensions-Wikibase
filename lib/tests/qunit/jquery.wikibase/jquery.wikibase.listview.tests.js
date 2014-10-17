/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, QUnit ) {
	'use strict';

	/**
	 * Initializes a listview widget suitable for testing.
	 *
	 * @param {*[]} [value]
	 * @param {Object} [options]
	 * @return {jQuery}
	 */
	function createListview( value, options ) {
		var $node = $( '<div>' ).addClass( 'test_listview' );

		options = $.extend( {
			listItemAdapter: new $.wikibase.listview.ListItemAdapter( {
				listItemWidget: $.wikibasetest.valuewidget,
				newItemOptionsFn: function( value ) {
					return { value: value || null };
				}
			} ),
			value: ( value ) ? value : null
		}, options || {} );

		$node.listview( options );

		return $node;
	}

	/**
	 * Returns the joined string values of all the generic value widgets within a listview.
	 *
	 * @param {jquery.wikibase.listview} listview
	 * @return {string}
	 */
	function getListItemStrings( listview ) {
		var value = '';

		$.each( listview.value(), function( i, valueWidget ) {
			value += valueWidget.value();
		} );

		return value;
	}

	QUnit.module( 'jquery.wikibase.listview', QUnit.newMwEnvironment( {
		setup: function() {
			/**
			 * Basic widget to be used as list item.
			 */
			$.widget( 'wikibasetest.valuewidget', {
				value: function( value ) {
					if( value ) {
						this.options.value = value;
					}
					return this.options.value;
				}
			} );
		},
		teardown: function() {
			$( '.test_listview' ).each( function( i, node ) {
				var $node = $( node ),
					listview = $node.data( 'listview' );

				if( listview ) {
					listview.destroy();
				}

				$node.remove();
			} );

			delete( $.wikibasetest.valuewidget );
		}
	} ) );

	QUnit.test( 'Initialize and destroy', function( assert ) {

		/**
		 * Runs assertions testing initialization and destruction of a listview widget initialized
		 * with the values passed.
		 *
		 * @param {Object} assert
		 * @param {string[]} [values]
		 */
		function testInitAndDestroy( assert, values ) {
			var $node = createListview( values ),
				listview = $node.data( 'listview' ),
				valuesLength = ( values ) ? values.length : 0;

			assert.ok(
				listview !== undefined,
				'Instantiated listview widget.'
			);

			assert.equal(
				listview.items().length,
				valuesLength,
				'Listview does not feature any items.'
			);

			assert.equal(
				listview.value().length,
				valuesLength,
				'Listview does not return an array of values.'
			);

			assert.equal(
				listview.nonEmptyItems().length,
				valuesLength,
				'Listview does not feature any items not empty.'
			);

			listview.destroy();

			assert.ok(
				$node.data( 'listview' ) === undefined,
				'Destroyed listview.'
			);

			assert.strictEqual(
				$node.children().length,
				valuesLength,
				'Reset listview node to initial state.'
			);

			$node.remove();
		}

		testInitAndDestroy( assert );
		testInitAndDestroy( assert, ['a'] );
		testInitAndDestroy( assert, ['a', 'b'] );
	} );

	QUnit.test( 'value()', function( assert ) {
		var $node = createListview(),
			listview = $node.data( 'listview' ),
			values = [
				['a', 'b', 'c'],
				['d']
			];

		assert.strictEqual(
			listview.value().length,
			0,
			'Listview is empty.'
		);

		listview.value( values[0] );

		assert.strictEqual(
			listview.value().length,
			3,
			'Set value via value().'
		);

		listview.value( values[1] );

		assert.strictEqual(
			listview.value().length,
			1,
			'Overwrote value via value().'
		);

		listview.value( [] );

		assert.strictEqual(
			listview.value().length,
			0,
			'Emptied listview via value().'
		);
	} );

	QUnit.test( 'addItem() and removeItem()', function( assert ) {
		var $node = createListview(),
			listview = $node.data( 'listview' ),
			values = ['a', 'b', 'c'],
			listItems = [];

		for( var i = 0; i < values.length; i++ ) {
			listview.addItem( values[i] );

			assert.strictEqual(
				listview.items().length,
				( i + 1 ),
				'Added item #' + i + ' to the list.'
			);

			assert.equal(
				listview.listItemAdapter().liInstance( listview.items().eq( i ) ).value(),
				values[i],
				'Retrieved listview\'s list item node for list item #' + i + '.'
			);

			listItems.push( listview.items().eq( i ) );
		}

		listview.removeItem( listItems[2] );

		assert.strictEqual(
			listview.items().length,
			2,
			'Removed third item from the list.'
		);

		listview.removeItem( listItems[0] );

		assert.strictEqual(
			listview.items().length,
			1,
			'Removed first item from the list.'
		);

		listview.removeItem( listItems[1] );

		assert.strictEqual(
			listview.items().length,
			0,
			'Removed second item from the list emptying the list.'
		);
	} );

	QUnit.test( 'enterNewItem()', function( assert ) {
		var $node = createListview(),
			listview = $node.data( 'listview' ),
			values = ['a', 'b', 'c'];

		listview.enterNewItem();

		assert.strictEqual(
			listview.items().length,
			1,
			'Inserted new (empty) item.'
		);

		assert.strictEqual(
			listview.nonEmptyItems().length,
			0,
			'Listview features no non-empty items.'
		);

		listview.addItem( values[0] );

		assert.strictEqual(
			listview.items().length,
			2,
			'Inserted a non-empty item.'
		);

		assert.strictEqual(
			listview.nonEmptyItems().length,
			1,
			'Listview features one non-empty item.'
		);

		listview.enterNewItem();

		assert.strictEqual(
			listview.items().length,
			3,
			'Inserted another new (empty) item.'
		);

		assert.strictEqual(
			listview.nonEmptyItems().length,
			1,
			'Listview features one non-empty item.'
		);

		listview.removeItem( listview.items().eq( 0 ) );

		assert.strictEqual(
			listview.items().length,
			2,
			'Removed first empty item.'
		);

		assert.strictEqual(
			listview.nonEmptyItems().length,
			1,
			'Listview features one non-empty item.'
		);

		listview.removeItem( listview.items().eq( 0 ) );

		assert.strictEqual(
			listview.items().length,
			1,
			'Removed non-empty item.'
		);

		assert.strictEqual(
			listview.nonEmptyItems().length,
			0,
			'Listview features no non-empty item.'
		);
	} );

	QUnit.test( 'listItemNodeName option', function( assert ) {
		var $node = createListview( ['a', 'b', 'c'], { listItemNodeName: 'SPAN' } ),
			listview = $node.data( 'listview' );

		assert.equal(
			$node.children( 'span' ).length,
			3,
			'Initialized listview with non-default list item nodes.'
		);

		listview.removeItem( $node.children().first() );
		listview.enterNewItem();

		assert.equal(
			$node.children( 'span' ).length,
			3,
			'Listview item node type remains the same after manipulations.'
		);
	} );

	QUnit.test( 'indexOf()', function( assert ) {
		var $node = createListview( ['a', 'b', 'c'] ),
			listview = $node.data( 'listview' );

		for( var i = 0; i < listview.items().length; i++ ) {
			assert.strictEqual(
				listview.indexOf( listview.items().eq( i ) ),
				i,
				'Validated index of list item #' + i + '.'
			);
		}
	} );

	QUnit.test( 'move()', 56, function( assert ) {
		var values = ['a', 'b', 'c', 'd'],
			$node,
			listview;

		/**
		 * 0 => Index of item to move
		 * 1 => Index to move item to
		 * 2 => Expected resulting order
		 * @type {*[][]}
		 */
		var testCases = [
			[0, 0, 'abcd'], // no event
			[0, 1, 'abcd'], // no event
			[0, 2, 'bacd'],
			[0, 3, 'bcad'],
			[0, 4, 'bcda'],
			[1, 0, 'bacd'],
			[1, 1, 'abcd'], // no event
			[1, 2, 'abcd'], // no event
			[1, 3, 'acbd'],
			[1, 4, 'acdb'],
			[2, 0, 'cabd'],
			[2, 1, 'acbd'],
			[2, 2, 'abcd'], // no event
			[2, 3, 'abcd'], // no event
			[2, 4, 'abdc'],
			[3, 0, 'dabc'],
			[3, 1, 'adbc'],
			[3, 2, 'abdc'],
			[3, 3, 'abcd'], // no event
			[3, 4, 'abcd'] // no event
		];

		$.each( testCases, function( i, testCase ) {
			$node = createListview( values );
			listview = $node.data( 'listview' );

			$node.one( 'listviewafteritemmove', function( event, newIndex, listLength ) {
				assert.ok(
					true,
					'Triggered "afteritemmove" event.'
				);

				assert.equal(
					newIndex,
					testCase[2].indexOf( values[testCase[0]]),
					'Verified new item index.'
				);

				assert.equal(
					listLength,
					testCase[2].length,
					'Verified list length.'
				);
			} );

			listview.move( listview.items().eq( testCase[0] ), testCase[1] );

			$node.off( 'listviewafteritemmove' );

			assert.equal(
				getListItemStrings( listview ),
				testCase[2],
				'Moving item #' + testCase[0] + ' to index #' + testCase[1] + ' results in order "'
					+ testCase[2] + '".'
			);
		} );
	} );

	QUnit.test( 'moveUp() and moveDown()', function( assert ) {
		var values = ['a', 'b', 'c', 'd'];

		/**
		 * The key specifies the objects method to call. The value represents the expected resulting
		 * order when issuing the method on the item that has the same index than the result.
		 * @type {Object}
		 */
		var testCases = {
			'moveUp': [
				'abcd',
				'bacd',
				'acbd',
				'abdc'
			],
			'moveDown': [
				'bacd',
				'acbd',
				'abdc',
				'abcd'
			]
		};

		var $node, listview;

		$.each( testCases, function( methodName, expectedResults ) {
			$.each( expectedResults, function( i, expected ) {
				$node = createListview( values );
				listview = $node.data( 'listview' );

				listview[methodName]( listview.items().eq( i ) );

				assert.equal(
					getListItemStrings( listview ),
					expected,
					'Issuing ' + methodName + '() on item #' + i + ' results in order "' + expected
						+ '".'
				);
			} );
		} );
	} );

} )( jQuery, QUnit );

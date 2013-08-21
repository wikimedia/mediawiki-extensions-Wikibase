/**
 * @since 0.4
 * @file
 * @ingroup WikibaseLib
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, mw, wb ) {
	'use strict';

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

	/**
	 * Initializes a listview widget suitable for testing.
	 *
	 * @param {*[]} [value]
	 * @return {jQuery}
	 */
	function createListview( value ) {
		var $node = $( '<div/>' ).addClass( 'test_listview' );

		$node.listview( {
			listItemAdapter: new $.wikibase.listview.ListItemAdapter( {
				listItemWidget: $.wikibasetest.valuewidget,
				listItemWidgetValueAccessor: 'value',
				newItemOptionsFn: function( value ) {
					return { value: value || null };
				}
			} ),
			value: ( value ) ? value : null
		} );

		return $node;
	}

	QUnit.module( 'jquery.wikibase.listview', window.QUnit.newWbEnvironment( {
		teardown: function() {
			$( '.test_listview' ).each( function( i, node ) {
				var $node = $( node ),
					listview = $node.data( 'listview' );

				if( listview ) {
					listview.destroy();
				}

				$node.remove();
			} );
		}
	} ) );

	QUnit.test( 'Initialize and destroy', function( assert ) {
		var $node = createListview(),
			listview = $node.data( 'listview' );

		assert.ok(
			listview !== undefined,
			'Instantiated listview widget.'
		);

		assert.strictEqual(
			listview.items().length,
			0,
			'Listview does not feature any items.'
		);

		assert.strictEqual(
			listview.value().length,
			0,
			'Listview does not return an array of values.'
		);

		assert.strictEqual(
			listview.nonEmptyItems().length,
			0,
			'Listview does not feature any items not empty.'
		);

		listview.destroy();

		assert.ok(
			$node.data( 'listview' ) === undefined,
			'Destroyed listview.'
		);

		assert.strictEqual(
			$node.children().length,
			0,
			'Reset listview node to initial state.'
		);

		$node.remove();

		var values = ['a'];

		$node = createListview( values );
		listview = $node.data( 'listview' );

		assert.strictEqual(
			listview.items().length,
			1,
			'Initialized listview with one item.'
		);

		assert.strictEqual(
			listview.value().length,
			1,
			'Listview returns array containing an item.'
		);

		assert.strictEqual(
			listview.nonEmptyItems().length,
			1,
			'Listview features one item not empty.'
		);

		listview.destroy();

		assert.ok(
			$node.data( 'listview' ) === undefined,
			'Destroyed listview.'
		);

		assert.strictEqual(
			$node.children().length,
			1,
			'Reset listview node to initial state without removing the list item node.'
		);

		$node.remove();

		values.push( 'b' );

		$node = createListview( values );
		listview = $node.data( 'listview' );

		assert.strictEqual(
			listview.items().length,
			2,
			'Initialized listview with two items.'
		);

		assert.strictEqual(
			listview.value().length,
			2,
			'Listview returns array containing two items.'
		);

		assert.strictEqual(
			listview.nonEmptyItems().length,
			2,
			'Listview features two items not empty.'
		);

		listview.destroy();

		assert.ok(
			$node.data( 'listview' ) === undefined,
			'Destroyed listview.'
		);

		assert.strictEqual(
			$node.children().length,
			2,
			'Reset listview node to initial state without removing the list item nodes.'
		);

		$node.remove();
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

		assert.strictEqual(
			listview.value( values[0] ).length,
			3,
			'Set value via value().'
		);

		assert.strictEqual(
			listview.value().length,
			3,
			'Confirmed current value by calling value() without any parameter.'
		);

		assert.strictEqual(
			listview.value( values[1] ).length,
			1,
			'Overwrote value via value().'
		);

		assert.strictEqual(
			listview.value().length,
			1,
			'Confirmed overwritten value by calling value() without any parameter.'
		);

		assert.strictEqual(
			listview.value( [] ).length,
			0,
			'Emptied listview via value().'
		);

		assert.strictEqual(
			listview.value().length,
			0,
			'Confirmed empty listview by calling value() without any parameter.'
		);
	} );

	QUnit.test( 'Add and remove items', function( assert ) {
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
				listview.listItemAdapter().liValue( listview.items().eq( i ) ),
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

	QUnit.test( 'Enter new items', function( assert ) {
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

	QUnit.test( 'Moving items', function( assert ) {
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

		/**
		 * Returns the joined string values of all the widgets within the listview.
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

		// Testing generic move() method:

		/**
		 * 0 => Index of item to move
		 * 1 => Index to move item to
		 * 2 => Expected resulting order
		 * @type {*[][]}
		 */
		testCases = [
			[0, 0, 'abcd'],
			[0, 1, 'abcd'],
			[0, 2, 'bacd'],
			[0, 3, 'bcad'],
			[0, 4, 'bcda'],
			[1, 0, 'bacd'],
			[1, 1, 'abcd'],
			[1, 2, 'abcd'],
			[1, 3, 'acbd'],
			[1, 4, 'acdb'],
			[2, 0, 'cabd'],
			[2, 1, 'acbd'],
			[2, 2, 'abcd'],
			[2, 3, 'abcd'],
			[2, 4, 'abdc'],
			[3, 0, 'dabc'],
			[3, 1, 'adbc'],
			[3, 2, 'abdc'],
			[3, 3, 'abcd'],
			[3, 4, 'abcd']
		];

		$.each( testCases, function( i, testCase ) {
			$node = createListview( values );
			listview = $node.data( 'listview' );

			listview.move( listview.items().eq( testCase[0] ), testCase[1] );

			assert.equal(
				getListItemStrings( listview ),
				testCase[2],
				'Moving item #' + testCase[0] + ' to index #' + testCase[1] + ' results in order "'
					+ testCase[2] + '".'
			);
		} );
	} );

} )( jQuery, mediaWiki, wikibase );

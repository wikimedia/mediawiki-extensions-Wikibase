/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, QUnit, wb ) {
	'use strict';

	/**
	 * Initializes a listview widget suitable for testing.
	 *
	 * @param {*[]} [value]
	 * @param {Object} [options]
	 * @return {jQuery}
	 */
	function createListview( value, options ) {
		var $node = $( '<div/>' ).addClass( 'test_listview' );

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

	QUnit.module( 'jquery.wikibase.listview', QUnit.newMwEnvironment( {
		setup: function() {
			/**
			 * Basic widget to be used as list item.
			 */
			$.widget( 'wikibasetest.valuewidget', {
				value: function( value ) {
					if ( value ) {
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

				if ( listview ) {
					listview.destroy();
				}

				$node.remove();
			} );

			delete( $.wikibasetest.valuewidget );
		}
	} ) );

	QUnit.test( 'Initialize and destroy', function( assert ) {
		assert.expect( 18 );

		/**
		 * Runs assertions testing initialization and destruction of a listview widget initialized
		 * with the values passed.
		 *
		 * @param {QUnit.assert} assert
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
		assert.expect( 4 );
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
		assert.expect( 9 );
		var $node = createListview(),
			listview = $node.data( 'listview' ),
			values = ['a', 'b', 'c'],
			listItems = [];

		for ( var i = 0; i < values.length; i++ ) {
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
		assert.expect( 10 );
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
		assert.expect( 2 );
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
		assert.expect( 3 );
		var $node = createListview( ['a', 'b', 'c'] ),
			listview = $node.data( 'listview' );

		for ( var i = 0; i < listview.items().length; i++ ) {
			assert.strictEqual(
				listview.indexOf( listview.items().eq( i ) ),
				i,
				'Validated index of list item #' + i + '.'
			);
		}
	} );

	QUnit.test( 'startEditing', function( assert ) {
		assert.expect( 2 );
		var listItemAdapter = wb.tests.getMockListItemAdapter(
			'test',
			function() {
				this.startEditing = function() {
					var deferred = $.Deferred();
					setTimeout( deferred.resolve, 0 );
					return deferred.promise();
				};
			}
		);
		var $node = createListview(
				['a', 'b', 'c'],
				{ listItemAdapter: listItemAdapter }
			),
			listview = $node.data( 'listview' );

		var result = listview.startEditing();
		assert.strictEqual( result.state(), 'pending' );
		result.done( function() {
			QUnit.start();
			assert.strictEqual( result.state(), 'resolved' );
		} );
		QUnit.stop();
	} );

	QUnit.test( 'reuse items', function( assert ) {
		assert.expect( 1 );
		var $node = $( document.createElement( 'span' ) );
		$node.append( document.createElement( 'span' ) ).append( document.createElement( 'span' ) );
		var listview = $node.listview( {
			listItemAdapter: new $.wikibase.listview.ListItemAdapter( {
				listItemWidget: $.wikibasetest.valuewidget,
				newItemOptionsFn: function () {}
			} ),
			listItemNodeName: 'span'
		} ).data( 'listview' );
		assert.equal( listview.value().length, 2 );
	} );

} )( jQuery, QUnit, wikibase );

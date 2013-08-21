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

} )( jQuery, mediaWiki, wikibase );

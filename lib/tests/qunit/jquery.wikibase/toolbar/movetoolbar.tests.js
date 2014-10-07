/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( $, QUnit ) {
	'use strict';

	/**
	 * Generates a listview widget suitable for testing.
	 *
	 * @param {*[]} [value]
	 * @return {jQuery}
	 */
	function createListview( value ) {
		var $node = $( '<div/>' ).addClass( 'test_listview' );

		$node.listview( {
			listItemAdapter: new $.wikibase.listview.ListItemAdapter( {
				listItemWidget: $.wikibasetest.valuewidget,
				newItemOptionsFn: function( value ) {
					return { value: value || null };
				}
			} ),
			value: ( value ) ? value : null
		} );

		return $node;
	}

	/**
	 * Generates a movetoolbar widget suitable for testing.
	 *
	 * @param {*[]} listviewValue
	 * @return {jQuery}
	 */
	function createListViewWithMovetoolbar( listviewValue ) {
		var $listview = createListview( listviewValue ),
			listview = $listview.data( 'listview' );

		listview.items().each( function( i, itemNode ) {
			$( itemNode ).movetoolbar( { listView: listview } );
		} );

		return $listview;
	}

	QUnit.module( 'jquery.wikibase.movetoolbar', QUnit.newMwEnvironment( {
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
					listview.items().each( function( j, itemNode ) {
						var $itemNode = $( itemNode ),
							movetoolbar = $itemNode.data( 'movetoolbar' );

						if( movetoolbar ) {
							movetoolbar.destroy();
						}
					} );

					listview.destroy();
					$node.remove();
				}
			} );

			delete( $.wikibasetest.valuewidget );
		}
	} ) );

	QUnit.test( 'Initialize and destroy', function( assert ) {
		var items = ['a', 'b', 'c'],
			$listview = createListViewWithMovetoolbar( items ),
			listview = $listview.data( 'listview' ),
			$toolbar = listview.items().first(),
			toolbar = $toolbar.data( 'movetoolbar' );

		assert.ok(
			toolbar !== undefined,
			'Initialized widget.'
		);

		assert.equal(
			toolbar.toolbar.element.children().length,
			2,
			'Toolbar features two child nodes.'
		);

		assert.equal(
			toolbar.$btnMoveUp.get( 0 ),
			toolbar.toolbar.element.children().get( 0 ),
			'First child node is the "move up" button.'
		);

		assert.equal(
			toolbar.$btnMoveDown.get( 0 ),
			toolbar.toolbar.element.children().get( 1 ),
			'Second child node is the "move down" button.'
		);

		toolbar.destroy();

		assert.equal(
			$toolbar.data( 'toolbar' ),
			null,
			'Destroyed widget.'
		);

		assert.strictEqual(
			listview.items().length,
			items.length,
			'Items in referenced listview widget remain.'
		);
	} );

	QUnit.test( 'Button events', 2, function( assert ) {
		var items = ['a', 'b', 'c'],
			$listview = createListViewWithMovetoolbar( items ),
			listview = $listview.data( 'listview' ),
			$toolbar = listview.items().first(),
			toolbar = $toolbar.data( 'movetoolbar' );

		$toolbar.on( 'movetoolbarup', function( event ) {
			assert.ok(
				true,
				'Triggered "up" event.'
			);
		} );

		$toolbar.on( 'movetoolbardown', function( event ) {
			assert.ok(
				true,
				'Triggered "down" event.'
			);
		} );

		toolbar.$btnMoveUp.trigger( 'click' );
		toolbar.$btnMoveDown.trigger( 'click' );
	} );

}( jQuery, QUnit ) );

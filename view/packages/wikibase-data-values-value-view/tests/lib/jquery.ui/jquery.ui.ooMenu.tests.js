/**
 * @license GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function () {
	'use strict';

	/**
	 * Factory for creating a jQuery.ui.ooMenu widget suitable for testing.
	 *
	 * @param {Object} [options]
	 *        Default: { maxItems: 4 }
	 */
	var newTestMenu = function( options ) {
		options = $.extend( { maxItems: 4 }, options || {} );

		return $( '<ul/>' )
			.addClass( 'test_ooMenu' )
			.appendTo( 'body' )
			.ooMenu( options );
	};

	var menuItems = [
		new $.ui.ooMenu.Item( 'item 0' ),
		new $.ui.ooMenu.Item( 'item 1' ),
		new $.ui.ooMenu.Item( 'item 2' ),
		new $.ui.ooMenu.Item( 'item 3' ),
		new $.ui.ooMenu.Item( 'item 4' ),
		new $.ui.ooMenu.Item( 'item 5' )
	];

	var customMenuItems = [
		new $.ui.ooMenu.CustomItem( 'customItem' ),
		new $.ui.ooMenu.CustomItem( 'whenFilled', function( menu ) {
			return menu.hasVisibleItems();
		} ),
		new $.ui.ooMenu.CustomItem( 'whenEmpty', function( menu ) {
			return !menu.hasVisibleItems();
		} )
	];

	QUnit.module( 'jquery.ui.ooMenu', {
		afterEach: function() {
			$( '.test_ooMenu' ).remove();
		}
	} );

	QUnit.test( 'Create', function( assert ) {
		var $menu = newTestMenu(),
			menu = $menu.data( 'ooMenu' );

		assert.ok(
			menu instanceof $.ui.ooMenu,
			'Instantiated empty menu widget.'
		);

		assert.ok(
			menu.option( 'items' ).length === 0 && $menu.children().length === 0,
			'Verified menu being empty.'
		);

		$menu = newTestMenu( {
			items: menuItems
		} );
		menu = $menu.data( 'ooMenu' );

		assert.ok(
			$menu.data( 'ooMenu' ) instanceof $.ui.ooMenu,
			'Instantiated menu widget filled with items.'
		);

		assert.strictEqual(
			menu.option( 'items' ).length, menuItems.length,
			'Verified items set.'
		);

		assert.strictEqual(
			$menu.children().length, menuItems.length,
			'Verified DOM structure.'
		);

		$menu = newTestMenu( {
			items: menuItems,
			customItems: customMenuItems
		} );
		menu = $menu.data( 'ooMenu' );

		assert.ok(
			$menu.data( 'ooMenu' ) instanceof $.ui.ooMenu,
			'Instantiated menu widget filled with items and custom items.'
		);

		assert.ok(
			menu.option( 'items' ).length === menuItems.length
				&& menu.option( 'customItems' ).length === customMenuItems.length
				&& $menu.children().length === menuItems.length + customMenuItems.length - 1,
			'Verified menu being filled.'
		);
	} );

	QUnit.test( 'hasVisibleItems()', function( assert ) {
		var $menu = newTestMenu(),
			menu = $menu.data( 'ooMenu' );

		assert.strictEqual(
			menu.hasVisibleItems(), false,
			'Empty menu has no visible default items.'
		);

		assert.strictEqual(
			menu.hasVisibleItems( true ), false,
			'Empty menu has no visible items at all.'
		);

		menu.option( 'customItems', customMenuItems );

		assert.strictEqual(
			menu.hasVisibleItems(), false,
			'Menu filled with custom items only has no visible default items.'
		);

		assert.ok(
			menu.hasVisibleItems( true ),
			'Menu filled with custom items has visible items.'
		);

		menu.option( 'customItems', [new $.ui.ooMenu.CustomItem( 'test', function( menu ) {
			return menu.hasVisibleItems();
		} ) ] );

		assert.strictEqual(
			menu.hasVisibleItems( true ), false,
			'Menu filled with an invisible custom item has no visible items at all.'
		);

		menu.option( 'items', menuItems );
		menu.option( 'customItems', [] );

		assert.ok(
			menu.hasVisibleItems(),
			'Menu filled with default items has visible default items.'
		);

		assert.ok(
			menu.hasVisibleItems( true ),
			'Menu filled with default items has visible items.'
		);

		menu.option( 'customItems', customMenuItems );

		assert.ok(
			menu.hasVisibleItems(),
			'Menu filled with default and custom items has visible default items.'
		);

		assert.ok(
			menu.hasVisibleItems( true ),
			'Menu filled with default and custom items has visible items.'
		);
	} );

	QUnit.test( 'Update items using option()', function( assert ) {
		var $menu = newTestMenu(),
			menu = $menu.data( 'ooMenu' );

		menu.option( 'items', menuItems );

		assert.strictEqual(
			$menu.children().length,
			menuItems.length,
			'Updated empty menu with items.'
		);

		menu.option( 'items', [
			new $.ui.ooMenu.Item( 'test' )
		] );

		assert.strictEqual(
			$menu.children().length,
			1,
			'Updated menu with single item.'
		);

		menu.option( 'items', [] );

		assert.strictEqual(
			$menu.children().length,
			0,
			'Removed all items.'
		);
	} );

	QUnit.test( 'Update items using option() with custom items present', function( assert ) {
		var $menu = newTestMenu( { customItems: customMenuItems } ),
			menu = $menu.data( 'ooMenu' );

		menu.option( 'items', menuItems );

		assert.strictEqual(
			$menu.children().length,
			menuItems.length + customMenuItems.length - 1,
			'Updated empty menu with items.'
		);

		menu.option( 'items', [
			new $.ui.ooMenu.Item( 'test' )
		] );

		assert.strictEqual(
			$menu.children().length,
			customMenuItems.length - 1 + 1,
			'Updated menu with single item.'
		);

		menu.option( 'items', [] );

		assert.strictEqual(
			$menu.children().length,
			customMenuItems.length - 1,
			'Removed all items.'
		);
	} );

	QUnit.test( 'Update custom items using option()', function( assert ) {
		var $menu = newTestMenu(),
			menu = $menu.data( 'ooMenu' );

		menu.option( 'customItems', customMenuItems );

		assert.strictEqual(
			$menu.children().length,
			customMenuItems.length - 1,
			'Updated empty menu with custom items.'
		);

		menu.option( 'customItems', [
			new $.ui.ooMenu.CustomItem( 'test' )
		] );

		assert.strictEqual(
			$menu.children().length,
			1,
			'Updated menu with single custom item.'
		);

		menu.option( 'customItems', [] );

		assert.strictEqual(
			$menu.children().length,
			0,
			'Removed all custom items.'
		);
	} );

	QUnit.test( 'Update custom items using option() with items present', function( assert ) {
		var $menu = newTestMenu( { customItems: customMenuItems } ),
			menu = $menu.data( 'ooMenu' );

		menu.option( 'items', menuItems );

		assert.strictEqual(
			$menu.children().length,
			menuItems.length + customMenuItems.length - 1,
			'Updated empty menu with items.'
		);

		menu.option( 'items', [
			new $.ui.ooMenu.Item( 'test' )
		] );

		assert.strictEqual(
			$menu.children().length,
			customMenuItems.length - 1 + 1,
			'Updated menu with single item.'
		);

		menu.option( 'items', [] );

		assert.strictEqual(
			$menu.children().length,
			customMenuItems.length - 1,
			'Removed all items.'
		);
	} );

	QUnit.test( 'Setting "maxItems" option triggering scale()', function( assert ) {
		var $menu = newTestMenu( {
				items: menuItems,
				maxItems: menuItems.length - 1
			} ),
			menu = $menu.data( 'ooMenu' );

		var maxHeight = $menu.outerHeight();

		menu.option( 'maxItems', 1 );

		assert.ok(
			$menu.outerHeight() < maxHeight,
			'Lowered "maxItems" option scaling down menu size.'
		);

		menu.option( 'maxItems', menuItems.length - 1 );

		assert.strictEqual(
			$menu.outerHeight(),
			maxHeight,
			'Reset "maxItems" option rescaling the menu size.'
		);
	} );

	QUnit.test( '"manipulateLabel" option', function( assert ) {
		var $menu = newTestMenu( {
				items: menuItems.slice( 0, 2 ),
				manipulateLabel: function( label ) {
					return 'manipulated label';
				}
			} );

		$menu.children( '.ui-ooMenu-item' ).each( function() {
			assert.strictEqual(
				$( this ).text(),
				'manipulated label'
			);
		} );
	} );

	QUnit.test( 'prev() & getActiveItem()', function( assert ) {
		var $menu = newTestMenu(),
			menu = $menu.data( 'ooMenu' );

		// This should not cause an error:
		menu.previous();

		menu.option( 'items', menuItems );

		menu.previous();

		assert.strictEqual(
			menu.getActiveItem(), menuItems[menuItems.length - 1],
			'Moving to last item if no item is active.'
		);

		// Move to first item:
		menu.next();

		menu.previous();

		assert.strictEqual(
			menu.getActiveItem(), menuItems[menuItems.length - 1],
			'Moving from first item to last item.'
		);

		menu.previous();

		assert.strictEqual(
			menu.getActiveItem(), menuItems[menuItems.length - 2],
			'Moving to previous item.'
		);
	} );

	QUnit.test( 'next() & getActiveItem()', function( assert ) {
		var $menu = newTestMenu(),
			menu = $menu.data( 'ooMenu' );

		// This should not cause an error:
		menu.next();

		menu.option( 'items', menuItems );

		menu.next();

		assert.strictEqual(
			menu.getActiveItem(), menuItems[0],
			'Moving to first item if no item is active.'
		);

		// Move to last item:
		menu.previous();

		menu.next();

		assert.strictEqual(
			menu.getActiveItem(), menuItems[0],
			'Moving from last item to first item.'
		);

		menu.next();

		assert.strictEqual(
			menu.getActiveItem(), menuItems[1],
			'Moving to next item.'
		);
	} );

	QUnit.test( 'select() on Item instances', function( assert ) {
		var $menu = newTestMenu( { items: menuItems } ),
			menu = $menu.data( 'ooMenu' );

		$( menu )
		.on( 'selected', function( event, item ) {
			assert.strictEqual(
				item, null,
				'Event transmits "null" if no item is active when selecting.'
			);
		} );

		menu.select();

		menu.previous();

		$( menu )
		.off( 'selected' )
		.on( 'selected', function( event, item ) {
			assert.strictEqual(
				item, menuItems[menuItems.length - 1],
				'Verified selected item.'
			);
		} );

		menu.select();
	} );

	QUnit.test( 'select() on CustomInstance instances', function( assert ) {
		var check = false;

		var customItem = new $.ui.ooMenu.CustomItem(
			'label',
			function() {
				return true;
			},
			function() {
				check = true;
			}
		);

		var $menu = newTestMenu( {
			customItems: [customItem]
		} );

		var menu = $menu.data( 'ooMenu' );

		menu.previous();

		$( menu )
		.on( 'selected', function( event, item ) {
			assert.strictEqual(
				item, customItem,
				'Verified selected item.'
			);

			assert.ok(
				check,
				'Issued custom action.'
			);
		} );

		menu.select();
	} );

	QUnit.test( 'activate()', function( assert ) {
		var $menu = newTestMenu( { items: menuItems } ),
			menu = $menu.data( 'ooMenu' );

		assert.throws(
			function() {
				menu.activate();
			},
			'Throwing error when calling "activate" without any parameter.'
		);

		assert.throws(
			function() {
				menu.activate( 'test' );
			},
			'Throwing error when calling "activate" with an incorrect parameter.'
		);

		$( menu ).on( 'focus', function( event, item ) {
			assert.strictEqual(
				item,
				menuItems[0],
				'Verified activated item.'
			);
		} );

		menu.activate( menuItems[0] );

		menu.deactivate();

		menu.activate( $menu.children( '.ui-ooMenu-item' ) );
	} );

	QUnit.test( 'deactivate()', function( assert ) {
		var $menu = newTestMenu( { items: menuItems } ),
			menu = $menu.data( 'ooMenu' );

		menu.previous();

		$( menu ).on( 'blur', function() {
			assert.ok(
				true,
				'Triggering "blur" event when deactivating'
			);

			assert.strictEqual(
				menu.getActiveItem(),
				null,
				'Verified no item being active.'
			);
		} );

		menu.deactivate();
	} );

	QUnit.test( 'Triggering "focus" event', function( assert ) {
		var customItem = new $.ui.ooMenu.CustomItem( 'label' );

		var $menu = newTestMenu( {
			items: menuItems,
			customItems: [customItem]
		} );

		var menu = $menu.data( 'ooMenu' );

		$( menu )
		.on( 'focus', function( event, item ) {
			assert.strictEqual(
				item, customItem,
				'Activated custom item.'
			);
		} );

		menu.previous();

		$( menu )
		.off( 'focus' )
		.on( 'focus', function( event, item ) {
			assert.strictEqual(
				item, menuItems[0],
				'Activated default item.'
			);
		} );

		menu.next();
	} );

	QUnit.test( 'Item constructor', function( assert ) {
		var item = new $.ui.ooMenu.Item( 'label' );

		assert.ok(
			item instanceof $.ui.ooMenu.Item,
			'Instantiated default item with plain string label.'
		);

		item = new $.ui.ooMenu.Item( $( '<div>label</div>' ) );

		assert.ok(
			item instanceof $.ui.ooMenu.Item,
			'Instantiated item with jQuery object label.'
		);
	} );

	QUnit.test( 'CustomItem constructor', function( assert ) {
		var testSets = [
			['label'],
			[$( '<div>label</div>' )],
			['label', true],
			['label', false],
			['label', function() { return true; }],
			['label', null, function() { return 'action'; }, 'cssClass', 'someLink']
		];

		for ( var i = 0; i < testSets.length; i++ ) {
			var args = testSets[i].concat( new Array( 5 - testSets[i].length ) ),
				item = new $.ui.ooMenu.CustomItem( args[0], args[1], args[2], args[3], args[4] );

			assert.ok(
				item instanceof $.ui.ooMenu.CustomItem,
				'Test set #' + i + ': Instantiated custom item.'
			);

			var expectedVisibility = true;

			if ( typeof testSets[i][1] === 'function' ) {
				expectedVisibility = testSets[i][1]();
			} else if ( typeof testSets[i][1] === 'boolean' ) {
				expectedVisibility = testSets[i][1];
			}

			assert.strictEqual(
				item.getVisibility(),
				expectedVisibility,
				'Test set #' + i + ': Verified getVisibile() return value.'
			);

			assert.strictEqual(
				item.getAction(),
				typeof testSets[i][2] === 'function' ? testSets[i][2] : null,
				'Test set #' + i + ': Verified getAction() return value.'
			);

			assert.strictEqual(
				item.getCssClass(),
				testSets[i][3] || '',
				'Test set #' + i + ': Verified getCssClass() return value.'
			);
		}
	} );

}() );

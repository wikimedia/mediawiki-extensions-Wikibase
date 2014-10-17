/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, QUnit ) {
	'use strict';

QUnit.module( 'jquery.wikibase.movetoolbar', QUnit.newMwEnvironment( {
	setup: function() {
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
		$( '.test_listview' ).each( function() {
			var $listview = $( this ),
				listview = $listview.data( 'listview' );

			if( listview ) {
				listview.items().each( function() {
					var $movetoolbar = $( this ),
						movetoolbar = $movetoolbar.data( 'movetoolbar' );

					if( movetoolbar ) {
						movetoolbar.destroy();
					}
				} );

				listview.destroy();
			}

			$listview.remove();
		} );

		delete( $.wikibasetest.valuewidget );
	}
} ) );

/**
 * @param {*[]} [value]
 * @return {jQuery}
 */
function createListview( value ) {
	var $listview = $( '<div>' ).addClass( 'test_listview' );

	$listview.listview( {
		listItemAdapter: new $.wikibase.listview.ListItemAdapter( {
			listItemWidget: $.wikibasetest.valuewidget,
			newItemOptionsFn: function( value ) {
				return { value: value || null };
			}
		} ),
		value: ( value ) ? value : null
	} );

	return $listview;
}

/**
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

QUnit.test( 'Create & destroy', function( assert ) {
	var items = ['a', 'b', 'c'],
		$listview = createListViewWithMovetoolbar( items ),
		listview = $listview.data( 'listview' ),
		$movetoolbar = listview.items().first(),
		movetoolbar = $movetoolbar.data( 'movetoolbar' );

	assert.ok(
		movetoolbar instanceof $.wikibase.movetoolbar,
		'Initialized widget.'
	);

	assert.equal(
		$movetoolbar.children().length,
		2,
		'Toolbar features two child nodes.'
	);

	assert.equal(
		$movetoolbar.children().first().data( 'toolbarbutton' ),
		movetoolbar.getButton( 'up' ),
		'First child node is the "move up" button.'
	);

	assert.equal(
		$movetoolbar.children().last().data( 'toolbarbutton' ),
		movetoolbar.getButton( 'down' ),
		'Last child node is the "move down" button.'
	);

	movetoolbar.destroy();

	assert.ok(
		!$movetoolbar.data( 'toolbar' ),
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
		$movetoolbar = listview.items().first(),
		movetoolbar = $movetoolbar.data( 'movetoolbar' );

	$movetoolbar.on( 'movetoolbarup', function( event ) {
		assert.ok(
			true,
			'Triggered "up" event.'
		);
	} );

	$movetoolbar.on( 'movetoolbardown', function( event ) {
		assert.ok(
			true,
			'Triggered "down" event.'
		);
	} );

	movetoolbar.getButton( 'up' ).element.children( 'a' ).trigger( 'click' );
	movetoolbar.getButton( 'down' ).element.children( 'a' ).trigger( 'click' );
} );

}( jQuery, QUnit ) );

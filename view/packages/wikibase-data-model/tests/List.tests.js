/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( QUnit ) {
'use strict';

var List = require( '../src/List.js' );

QUnit.module( 'List' );

/**
 * @constructor
 */
var TestItem = function() {};
TestItem.prototype.equals = function( other ) {
	return other === this;
};

/**
 * @param {number} n
 * @return {TestItem[]}
 */
function getTestItems( n ) {
	var items = [];

	for( var i = 0; i < n; i++ ) {
		items.push( new TestItem() );
	}

	return items;
}

QUnit.test( 'Constructor', function( assert ) {
	assert.expect( 6 );
	assert.ok(
		( new List( TestItem ) ) instanceof List,
		'Instantiated empty List.'
	);

	var items = getTestItems( 2 ),
		list = new List( TestItem, items );

	assert.ok(
		list instanceof List,
		'Instantiated filled List.'
	);

	assert.notStrictEqual(
		items,
		list._items,
		'Constructor does clone.'
	);

	assert.equal(
		list.length,
		2,
		'Verified list length.'
	);

	assert.throws(
		function() {
			return new List();
		},
		'Throwing error when trying to instantiate a List without an item constructor.'
	);

	assert.throws(
		function() {
			return new List( 'string' );
		},
		'Throwing error when trying to instantiate a List with an improper item constructor.'
	);
} );

QUnit.test( 'each()', function( assert ) {
	assert.expect( 2 );
	var items = getTestItems( 2 ),
		list = new List( TestItem, items );

	list.each( function( i, item ) {
		assert.ok(
			item.equals( items[i] ),
			'Verified received item and index.'
		);
	} );
} );

QUnit.test( 'toArray()', function( assert ) {
	assert.expect( 2 );
	var item = new TestItem(),
		list = new List( TestItem, [item] ),
		actual = list.toArray();

	assert.ok(
		actual.length === 1 && actual[0] === item,
		'toArray() returns original items.'
	);

	assert.strictEqual(
		list.toArray(),
		actual,
		'toArray() does not clone.'
	);
} );

QUnit.test( 'hasItem()', function( assert ) {
	assert.expect( 2 );
	var items = getTestItems( 3 ),
		list = new List( TestItem, items );

	assert.ok(
		list.hasItem( items[2] ),
		'Verified hasClaim() returning TRUE.'
	);

	assert.ok(
		!list.hasItem( getTestItems( 1 )[0] ),
		'Verified hasClaim() returning FALSE.'
	);
} );

QUnit.test( 'addItem() & length attribute', function( assert ) {
	assert.expect( 3 );
	var items = getTestItems( 3 ),
		newItems = getTestItems( 1 ),
		list = new List( TestItem, items );

	assert.equal(
		list.length,
		3,
		'List contains 3 items.'
	);

	list.addItem( newItems[0] );

	assert.ok(
		list.hasItem( newItems[0] ),
		'Added item.'
	);

	assert.equal(
		list.length,
		4,
		'Increased length.'
	);
} );

QUnit.test( 'removeItem()', function( assert ) {
	assert.expect( 4 );
	var items = getTestItems( 3 ),
		unsetItems = getTestItems( 1 ),
		list = new List( TestItem, items );

	assert.equal(
		list.length,
		3,
		'List contains 3 items.'
	);

	assert.throws(
		function() {
			list.removeItem( unsetItems[0] );
		},
		'Throwing error when trying to remove an item not set.'
	);

	list.removeItem( items[1] );

	assert.ok(
		!list.hasItem( items[1] ),
		'Removed item.'
	);

	assert.equal(
		list.length,
		2,
		'List contains 2 items.'
	);
} );

QUnit.test( 'isEmpty()', function( assert ) {
	assert.expect( 3 );
	var items = getTestItems( 1 ),
		list = new List( TestItem );

	assert.ok(
		list.isEmpty(),
		'Verified isEmpty() returning TRUE.'
	);

	list.addItem( items[0] );

	assert.ok(
		!list.isEmpty(),
		'Verified isEmpty() returning FALSE.'
	);

	list.removeItem( items[0] );

	assert.ok(
		list.isEmpty(),
		'TRUE after removing last Claim.'
	);
} );

QUnit.test( 'equals()', function( assert ) {
	assert.expect( 2 );
	var items = getTestItems( 3 ),
		list = new List( TestItem, items );

	assert.ok(
		list.equals( new List( TestItem, items ) ),
		'Verified equals() retuning TRUE.'
	);

	list.addItem( getTestItems( 1 )[0] );

	assert.ok(
		!list.equals( new List( TestItem, items ) ),
		'FALSE after adding another item object.'
	);
} );

QUnit.test( 'indexOf()', function( assert ) {
	assert.expect( 1 );
	var items = getTestItems( 3 ),
		list = new List( TestItem, items );

	assert.strictEqual(
		list.indexOf( items[1] ),
		1,
		'Retrieved correct index.'
	);
} );

}( QUnit ) );

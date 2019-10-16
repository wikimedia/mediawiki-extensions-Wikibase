/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( QUnit, $ ) {
'use strict';

var Map = require( '../src/Map.js' );

QUnit.module( 'Map' );

/**
 * @constructor
 * @param {string} key
 */
var TestItem = function( key ) {
	this._key = key;
};
$.extend( TestItem.prototype, {
	equals: function( other ) {
		return other === this;
	},
	getKey: function() {
		return this._key;
	}
} );

/**
 * @param {number} n
 * @return {Object}
 */
function getTestItems( n ) {
	var items = {};

	for( var i = 0; i < n; i++ ) {
		items['' + i] = new TestItem( '' + i );
	}

	return items;
}

/**
 * @param {Object} [map]
 * @return {Map}
 */
function createMap( map ) {
	return new Map( TestItem, map );
}

QUnit.test( 'Constructor', function( assert ) {
	assert.expect( 8 );
	assert.ok(
		createMap() instanceof Map,
		'Instantiated empty Map.'
	);

	var items = getTestItems( 2 ),
		map = createMap( items );

	assert.ok(
		map instanceof Map,
		'Instantiated filled Map.'
	);

	assert.notStrictEqual(
		items,
		map._items,
		'Constructor does clone.'
	);

	assert.equal(
		map.length,
		2,
		'Verified map length.'
	);

	map = createMap( { 'a': new TestItem( 'b' ) } );

	assert.ok(
		map instanceof Map,
		'Instantiated filled Map with asynchronous keys.'
	);

	assert.equal(
		map.length,
		1,
		'Verified Map length.'
	);

	assert.throws(
		function() {
			return new Map( null );
		},
		'Throwing error when trying to instantiate a Map without an item constructor.'
	);

	assert.throws(
		function() {
			return new Map( 'string' );
		},
		'Throwing error when trying to instantiate a Map with an improper item constructor.'
	);
} );

QUnit.test( 'each()', function( assert ) {
	assert.expect( 1 );
	var items = getTestItems( 2 ),
		map = createMap( items ),
		expectedKeys = [];

	for( var i = 0; i < items.length; i++ ) {
		expectedKeys.push( items[i].getKey() );
	}

	map.each( function( key, item ) {
		expectedKeys.splice( $.inArray( key, expectedKeys ), 1 );
	} );

	assert.strictEqual(
		expectedKeys.length,
		0,
		'Retrieved all expected keys.'
	);
} );

QUnit.test( 'getKeys()', function( assert ) {
	assert.expect( 4 );
	var items = getTestItems( 3 ),
		map = createMap( items ),
		keys = map.getKeys(),
		expectedKeys = [];

	$.each( items, function( key, value ) {
		if( $.inArray( key, keys ) !== -1 ) {
			assert.ok(
				true,
				'Found key ' + key + '.'
			);
			expectedKeys.push( key );
		}
	} );

	assert.strictEqual(
		keys.length,
		expectedKeys.length,
		'Verified number of keys.'
	);
} );

QUnit.test( 'getItemByKey()', function( assert ) {
	assert.expect( 4 );
	var items = getTestItems( 3 ),
		map = createMap( items );

	for( var i = 0; i < map.length; i++ ) {
		assert.ok(
			map.getItemByKey( items[i].getKey() ).equals( items[i] ),
			'Retrieved item by key ' + items[i].getKey() + '.'
		);
	}

	assert.strictEqual(
		map.getItemByKey( 'does-not-exist' ),
		null,
		'Returning NULL when no item is set for a particular key.'
	);
} );

QUnit.test( 'removeItemByKey() & length attribute', function( assert ) {
	assert.expect( 6 );
	var items = getTestItems( 2 ),
		map = createMap( items );

	assert.equal(
		map.length,
		2,
		'Map contains 2 items.'
	);

	map.removeItemByKey( '0' );

	assert.strictEqual(
		map.getItemByKey( '0' ),
		null,
		'Removed item.'
	);

	assert.strictEqual(
		map.length,
		1,
		'Map contains 1 item.'
	);

	map.removeItemByKey( 'does-not-exist' );

	assert.strictEqual(
		map.length,
		1,
		'Map contains 1 item after trying to remove an item that is not set.'
	);

	map.removeItemByKey( '1' );

	assert.strictEqual(
		map.getItemByKey( '1' ),
		null,
		'Removed item.'
	);

	assert.strictEqual(
		map.length,
		0,
		'Map is empty.'
	);
} );

QUnit.test( 'hasItemForKey()', function( assert ) {
	assert.expect( 4 );
	var items = getTestItems( 3 ),
		map = createMap( items );

	for( var i = 0; i < map.length; i++ ) {
		assert.ok(
			map.hasItemForKey( items[i].getKey() ),
			'Verified returning TRUE for key ' + items[i].getKey() + '.'
		);
	}

	assert.ok(
		!map.hasItemForKey( 'does-not-exist' ),
		'Verified returning FALSE.'
	);
} );

QUnit.test( 'setItem() & length attribute', function( assert ) {
	assert.expect( 6 );
	var items = getTestItems( 2 ),
		map = createMap( items ),
		newItem0 = getTestItems( 1 )[0],
		newItemWithSomeKey = new TestItem( 'someKey' );

	assert.equal(
		map.length,
		2,
		'Map contains 2 items.'
	);

	map.setItem( '0', newItem0 );

	assert.ok(
		map.getItemByKey( '0' ).equals( newItem0 ),
		'Overwrote item.'
	);

	assert.equal(
		map.length,
		2,
		'Length remains unchanged when overwriting an item.'
	);

	map.setItem( 'new', newItemWithSomeKey );

	assert.ok(
		map.getItemByKey( 'new' ).equals( newItemWithSomeKey ),
		'Added new item.'
	);

	assert.equal(
		map.length,
		3,
		'Increased length when adding new item.'
	);

	assert.throws(
		function() {
			map.setItem( 'key', ['string'] );
		},
		'Throwing error when trying to set a plain string array.'
	);
} );

QUnit.test( 'addItem()', function( assert ) {
	assert.expect( 2 );
	var items = getTestItems( 2 ),
		map = createMap( items ),
		item = getTestItems( 3 )[2];

	map.addItem( 'newKey', item );

	assert.ok(
		map.hasItem( 'newKey', item ),
		'Added item.'
	);

	assert.throws(
		function() {
			map.addItem( 'newKey', item );
		},
		'Throwing an error when trying to add an item featuring a key represented already.'
	);
} );

QUnit.test( 'removeItem()', function( assert ) {
	assert.expect( 4 );
	var items = getTestItems( 2 ),
		map = createMap( items );

	assert.equal(
		map.length,
		2,
		'Map contains 2 items.'
	);

	map.removeItem( '1', items[1] );

	assert.equal(
		map.length,
		1,
		'Map contains 1 item.'
	);

	assert.ok(
		!map.hasItem( '1', items[1] ),
		'Verified item being removed.'
	);

	assert.throws(
		function() {
			map.removeItem( '1', items[1] );
		},
		'Throwing an error when trying to remove an item not set.'
	);
} );

QUnit.test( 'isEmpty()', function( assert ) {
	assert.expect( 3 );
	var map = createMap(),
		item = getTestItems( 1 )[0];

	assert.ok(
		map.isEmpty(),
		'Verified isEmpty() returning TRUE.'
	);

	map.setItem( 'someKey', item );

	assert.ok(
		!map.isEmpty(),
		'Verified isEmpty() returning FALSE.'
	);

	map.removeItem( 'someKey', item );

	assert.ok(
		map.isEmpty(),
		'TRUE after removing last item.'
	);
} );

QUnit.test( 'equals()', function( assert ) {
	assert.expect( 3 );
	var items = getTestItems( 2 ),
		map = createMap( items );

	assert.ok(
		map.equals( createMap( items ) ),
		'Verified equals() retuning TRUE.'
	);

	map.setItem( 'someKey', getTestItems( 1 )[0] );

	assert.ok(
		!map.equals( createMap( items ) ),
		'FALSE when an item has been overwritten.'
	);

	map = createMap( items );
	map.removeItem( '1', items[1] );

	assert.ok(
		!map.equals( createMap( items ) ),
		'FALSE when an item has been removed.'
	);
} );

QUnit.test( 'hasItem()', function( assert ) {
	assert.expect( 3 );
	var items = getTestItems( 2 ),
		map = createMap( items );

	assert.ok(
		map.hasItem( '1', items[1] ),
		'Verified returning TRUE.'
	);

	assert.ok(
		!map.hasItem( '0', getTestItems( 1 )[0] ),
		'Verified returning FALSE.'
	);

	assert.throws(
		function() {
			map().hasItem( '1', '1' );
		},
		'Throwing error when submitting a string array.'
	);
} );

}( QUnit, jQuery ) );

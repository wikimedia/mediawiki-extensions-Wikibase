/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( QUnit, $ ) {
'use strict';

var Set = require( '../src/Set.js' );

QUnit.module( 'Set' );

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
 * @return {TestItem[]}
 */
function getTestItems( n ) {
	var items = [];

	for( var i = 0; i < n; i++ ) {
		items.push( new TestItem( '' + i ) );
	}

	return items;
}

/**
 * @param {TestItem[]} [items]
 * @return {Set}
 */
function createSet( items ) {
	return new Set( TestItem, 'getKey', items );
}

QUnit.test( 'Constructor', function( assert ) {
	assert.expect( 8 );
	assert.ok(
		createSet() instanceof Set,
		'Instantiated empty Set.'
	);

	var items = getTestItems( 2 ),
		set = createSet( items );

	assert.ok(
		set instanceof Set,
		'Instantiated filled Set.'
	);

	assert.notStrictEqual(
		items,
		set._items,
		'Constructor does clone.'
	);

	assert.equal(
		set.length,
		2,
		'Verified Set length.'
	);

	assert.throws(
		function() {
			return new Set( null, 'getKey' );
		},
		'Throwing error when trying to instantiate a Set without an item constructor.'
	);

	assert.throws(
		function() {
			return new Set( TestItem );
		},
		'Throwing error when trying to instantiate a Set without "getKey" function.'
	);

	assert.throws(
		function() {
			return new Set( 'string', 'getKey' );
		},
		'Throwing error when trying to instantiate a Set wit an improper item constructor.'
	);

	assert.throws(
		function() {
			return new Set( TestItem, 'doesNotExist' );
		},
		'Throwing error when trying to instantiate a Set with an improper "getKey" '
			+ 'function name.'
	);
} );

QUnit.test( 'each()', function( assert ) {
	assert.expect( 3 );
	var items = getTestItems( 2 ),
		set = createSet( items ),
		expectedKeys = [];

	for( var i = 0; i < items.length; i++ ) {
		expectedKeys.push( items[i].getKey() );
	}

	set.each( function( key, item ) {
		assert.equal(
			item.getKey(),
			key,
			'Verified matching key.'
		);
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
		set = createSet( items ),
		keys = set.getKeys(),
		expectedKeys = [];

	for( var i = 0; i < items.length; i++ ) {
		if( $.inArray( items[i].getKey(), keys ) !== -1 ) {
			assert.ok(
				true,
				'Found key ' + items[i].getKey() + '.'
			);
			expectedKeys.push( items[i].getKey() );
		}
	}

	assert.strictEqual(
		keys.length,
		expectedKeys.length,
		'Verified number of keys.'
	);
} );

QUnit.test( 'getItemByKey()', function( assert ) {
	assert.expect( 4 );
	var items = getTestItems( 3 ),
		set = createSet( items );

	for( var i = 0; i < items.length; i++ ) {
		assert.ok(
			set.getItemByKey( items[i].getKey() ).equals( items[i] ),
			'Retrieved item by key ' + items[i].getKey() + '.'
		);
	}

	assert.strictEqual(
		set.getItemByKey( 'does-not-exist' ),
		null,
		'Returning NULL when no item is Set for a particular key.'
	);
} );

QUnit.test( 'removeItemByKey() & length attribute', function( assert ) {
	assert.expect( 6 );
	var items = getTestItems( 2 ),
		set = createSet( items );

	assert.equal(
		set.length,
		2,
		'Set contains 2 items.'
	);

	set.removeItemByKey( '0' );

	assert.strictEqual(
		set.getItemByKey( '0' ),
		null,
		'Removed item.'
	);

	assert.strictEqual(
		set.length,
		1,
		'Set contains 1 item.'
	);

	set.removeItemByKey( 'does-not-exist' );

	assert.strictEqual(
		set.length,
		1,
		'Set contains 1 item after trying to remove an item that is not set.'
	);

	set.removeItemByKey( '1' );

	assert.strictEqual(
		set.getItemByKey( '1' ),
		null,
		'Removed item.'
	);

	assert.strictEqual(
		set.length,
		0,
		'Set is empty.'
	);
} );

QUnit.test( 'hasItemForKey()', function( assert ) {
	assert.expect( 4 );
	var items = getTestItems( 3 ),
		set = createSet( items );

	for( var i = 0; i < items.length; i++ ) {
		assert.ok(
			set.hasItemForKey( items[i].getKey() ),
			'Verified returning TRUE for key ' + items[i].getKey() + '.'
		);
	}

	assert.ok(
		!set.hasItemForKey( 'does-not-exist' ),
		'Verified returning FALSE.'
	);
} );

QUnit.test( 'setItem() & length attribute', function( assert ) {
	assert.expect( 6 );
	var items = getTestItems( 2 ),
		set = createSet( items ),
		newItem0 = getTestItems( 1 )[0],
		newItem2 = getTestItems( 3 )[2];

	assert.equal(
		set.length,
		2,
		'Set contains 2 items.'
	);

	set.setItem( newItem0 );

	assert.ok(
		set.getItemByKey( '0' ).equals( newItem0 ),
		'Overwrote item.'
	);

	assert.equal(
		set.length,
		2,
		'Length remains unchanged when overwriting an item.'
	);

	set.setItem( newItem2 );

	assert.ok(
		set.getItemByKey( '2' ).equals( newItem2 ),
		'Added new item.'
	);

	assert.equal(
		set.length,
		3,
		'Increased length when adding new item.'
	);

	assert.throws(
		function() {
			set.setItem( ['string'] );
		},
		'Throwing error when trying to set a plain string array.'
	);
} );

QUnit.test( 'addItem()', function( assert ) {
	assert.expect( 2 );
	var items = getTestItems( 2 ),
		set = createSet( items ),
		item = getTestItems( 3 )[2];

	set.addItem( item );

	assert.ok(
		set.hasItem( item ),
		'Added item.'
	);

	assert.throws(
		function() {
			set.addItem( item );
		},
		'Throwing an error when trying to add an item featuring a key represented already.'
	);
} );

QUnit.test( 'removeItem()', function( assert ) {
	assert.expect( 4 );
	var items = getTestItems( 2 ),
		set = createSet( items );

	assert.equal(
		set.length,
		2,
		'Set contains 2 items.'
	);

	set.removeItem( items[1] );

	assert.equal(
		set.length,
		1,
		'Set contains 1 item.'
	);

	assert.ok(
		!set.hasItem( items[1] ),
		'Verified item being removed.'
	);

	assert.throws(
		function() {
			set.removeItem( items[1] );
		},
		'Throwing an error when trying to remove an item not set.'
	);
} );

QUnit.test( 'isEmpty()', function( assert ) {
	assert.expect( 3 );
	var set = createSet(),
		item = getTestItems( 1 )[0];

	assert.ok(
		set.isEmpty(),
		'Verified isEmpty() returning TRUE.'
	);

	set.setItem( item );

	assert.ok(
		!set.isEmpty(),
		'Verified isEmpty() returning FALSE.'
	);

	set.removeItem( item );

	assert.ok(
		set.isEmpty(),
		'TRUE after removing last item.'
	);
} );

QUnit.test( 'equals()', function( assert ) {
	assert.expect( 3 );
	var items = getTestItems( 2 ),
		set = createSet( items );

	assert.ok(
		set.equals( createSet( items ) ),
		'Verified equals() retuning TRUE.'
	);

	set.setItem( getTestItems( 1 )[0] );

	assert.ok(
		!set.equals( createSet( items ) ),
		'FALSE when an item has been overwritten.'
	);

	set = createSet( items );
	set.removeItem( items[1] );

	assert.ok(
		!set.equals( createSet( items ) ),
		'FALSE when an item has been removed.'
	);
} );

QUnit.test( 'toArray()', function( assert ) {
	assert.expect( 2 );
	var item = getTestItems( 1 )[0],
		set = createSet( [item] ),
		actual = set.toArray();

	assert.ok(
		actual.length === 1 && actual[0] === item,
		'toArray() returns original items.'
	);

	assert.notStrictEqual(
		set.toArray(),
		actual,
		'toArray() does clone.'
	);
} );

QUnit.test( 'hasItem()', function( assert ) {
	assert.expect( 3 );
	var items = getTestItems( 2 ),
		set = createSet( items );

	assert.ok(
		set.hasItem( items[1] ),
		'Verified returning TRUE.'
	);

	assert.ok(
		!set.hasItem( getTestItems( 1 )[0] ),
		'Verified returning FALSE.'
	);

	assert.throws(
		function() {
			set().hasItem( '1' );
		},
		'Throwing error when submitting a string array.'
	);
} );

}( QUnit, jQuery ) );

/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit, $ ) {
'use strict';

QUnit.module( 'wikibase.datamodel.Set' );

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

function createList( items ) {
	return new wb.datamodel.Set( TestItem, 'getKey', items );
}

QUnit.test( 'Constructor', function( assert ) {
	assert.ok(
		createList() instanceof wb.datamodel.Set,
		'Instantiated empty Set.'
	);

	var list = createList( getTestItems( 2 ) );

	assert.ok(
		list instanceof wb.datamodel.Set,
		'Instantiated filled Set.'
	);

	assert.equal(
		list.length,
		2,
		'Verified list length.'
	);

	assert.throws(
		function() {
			return new wb.datamodel.Set( null, 'getKey' );
		},
		'Throwing error when trying to instantiate an Set without an item constructor.'
	);

	assert.throws(
		function() {
			return new wb.datamodel.Set( TestItem );
		},
		'Throwing error when trying to instantiate an Set without "getKey" function.'
	);

	assert.throws(
		function() {
			return new wb.datamodel.Set( 'string', 'getKey' );
		},
		'Throwing error when trying to instantiate an Set wit an improper item '
			+ 'constructor.'
	);

	assert.throws(
		function() {
			return new wb.datamodel.Set( TestItem, 'doesNotExist' );
		},
		'Throwing error when trying to instantiate an Set wit an improper "getKey" '
			+ 'function name.'
	);
} );

QUnit.test( 'each()', function( assert ) {
	var items = getTestItems( 2 ),
		list = createList( items ),
		expectedKeys = [];

	for( var i = 0; i < items.length; i++ ) {
		expectedKeys.push( items[i].getKey() );
	}

	list.each( function( key, item ) {
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
	var items = getTestItems( 3 ),
		list = createList( items ),
		keys = list.getKeys(),
		expectedKeys = [];

	for( var i = 0; i < items.length; i++ ) {
		assert.ok(
			$.inArray( items[i].getKey(), keys ) !== -1,
			'Found key ' + items[i].getKey() + '.'
		);
		expectedKeys.push( items[i].getKey() );
	}

	assert.strictEqual(
		keys.length,
		expectedKeys.length,
		'Verified count of keys.'
	);
} );

QUnit.test( 'getItemByKey()', function( assert ) {
	var items = getTestItems( 3 ),
		list = createList( items );

	for( var i = 0; i < items.length; i++ ) {
		assert.ok(
			list.getItemByKey( items[i].getKey() ).equals( items[i] ),
			'Retrieved item by key ' + items[i].getKey() + '.'
		);
	}

	assert.strictEqual(
		list.getItemByKey( 'does-not-exist' ),
		null,
		'Returning NULL when no item is set for a particular key.'
	);
} );

QUnit.test( 'removeByKey() & length attribute', function( assert ) {
	var items = getTestItems( 2 ),
		list = createList( items );

	assert.equal(
		list.length,
		2,
		'List contains 2 items.'
	);

	list.removeByKey( '0' );

	assert.strictEqual(
		list.getItemByKey( '0' ),
		null,
		'Removed item.'
	);

	assert.strictEqual(
		list.length,
		1,
		'List contains 1 item.'
	);

	list.removeByKey( 'does-not-exist' );

	assert.strictEqual(
		list.length,
		1,
		'List contains 1 item after trying to remove an item that is not set.'
	);

	list.removeByKey( '1' );

	assert.strictEqual(
		list.getItemByKey( '1' ),
		null,
		'Removed item.'
	);

	assert.strictEqual(
		list.length,
		0,
		'List is empty.'
	);
} );

QUnit.test( 'hasItemForKey()', function( assert ) {
	var items = getTestItems( 3 ),
		list = createList( items );

	for( var i = 0; i < items.length; i++ ) {
		assert.ok(
			list.hasItemForKey( items[i].getKey() ),
			'Verified returning TRUE for key ' + items[i].getKey() + '.'
		);
	}

	assert.ok(
		!list.hasItemForKey( 'does-not-exist' ),
		'Verified returning FALSE.'
	);
} );

QUnit.test( 'setItem() & length attribute', function( assert ) {
	var items = getTestItems( 2 ),
		list = createList( items ),
		newItem0 = getTestItems( 1 )[0],
		newItem2 = getTestItems( 3 )[2];

	assert.equal(
		list.length,
		2,
		'List contains 2 items.'
	);

	list.setItem( newItem0 );

	assert.ok(
		list.getItemByKey( '0' ).equals( newItem0 ),
		'Overwrote item.'
	);

	assert.equal(
		list.length,
		2,
		'Length remains unchanged when overwriting an item.'
	);

	list.setItem( newItem2 );

	assert.ok(
		list.getItemByKey( '2' ).equals( newItem2 ),
		'Added new item.'
	);

	assert.equal(
		list.length,
		3,
		'Increased length when adding new item.'
	);

	assert.throws(
		function() {
			list.setItem( ['string'] );
		},
		'Throwing error when trying to set a plain string array.'
	);
} );

QUnit.test( 'addItem()', function( assert ) {
	var items = getTestItems( 2 ),
		list = createList( items ),
		item = getTestItems( 3 )[2];

	list.addItem( item );

	assert.ok(
		list.hasItem( item ),
		'Added item.'
	);

	assert.throws(
		function() {
			list.addItem( item );
		},
		'Throwing an error when trying to add an item featuring a key represented already.'
	);
} );

QUnit.test( 'removeItem()', function( assert ) {
	var items = getTestItems( 2 ),
		list = createList( items );

	assert.equal(
		list.length,
		2,
		'List contains 2 items.'
	);

	list.removeItem( items[1] );

	assert.equal(
		list.length,
		1,
		'List contains 1 item.'
	);

	assert.ok(
		!list.hasItem( items[1] ),
		'Verified item being removed.'
	);

	assert.throws(
		function() {
			list.removeItem( items[1] );
		},
		'Throwing an error when trying to remove an item not set.'
	);
} );

QUnit.test( 'isEmpty()', function( assert ) {
	var list = createList(),
		item = getTestItems( 1 )[0];

	assert.ok(
		list.isEmpty(),
		'Verified isEmpty() returning TRUE.'
	);

	list.setItem( item );

	assert.ok(
		!list.isEmpty(),
		'Verified isEmpty() returning FALSE.'
	);

	list.removeItem( item );

	assert.ok(
		list.isEmpty(),
		'TRUE after removing last item.'
	);
} );

QUnit.test( 'equals()', function( assert ) {
	var items = getTestItems( 2 ),
		list = createList( items );

	assert.ok(
		list.equals( createList( items ) ),
		'Verified equals() retuning TRUE.'
	);

	list.setItem( getTestItems( 1 )[0] );

	assert.ok(
		!list.equals( createList( items ) ),
		'FALSE when an item has been overwritten.'
	);

	list = createList( items );
	list.removeItem( items[1] );

	assert.ok(
		!list.equals( createList( items ) ),
		'FALSE when an item has been removed.'
	);
} );

QUnit.test( 'hasItem()', function( assert ) {
	var items = getTestItems( 2 ),
		list = createList( items );

	assert.ok(
		list.hasItem( items[1] ),
		'Verified returning TRUE.'
	);

	assert.ok(
		!list.hasItem( getTestItems( 1 )[0] ),
		'Verified returning FALSE.'
	);

	assert.throws(
		function() {
			list().hasItem( '1' );
		},
		'Throwing error when submitting a string array.'
	);
} );

}( wikibase, QUnit, jQuery ) );

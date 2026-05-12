/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( QUnit, $, util ) {
'use strict';

var Group = require( '../src/Group.js' ),
	GroupableCollection = require( '../src/GroupableCollection.js' );

QUnit.module( 'Group' );

/**
 * @class TestItem
 * @constructor
 * @param {*} key
 * @param {*} value
 */
var TestItem = function( key, value ) {
	this._key = key;
	this._value = value;
};
$.extend( TestItem.prototype, {
	equals: function( other ) {
		return other.getValue() === this.getValue();
	},
	getKey: function() {
		return this._key;
	},
	getValue: function() {
		return this._value;
	}
} );

/**
 * @class TestContainer
 * @constructor
 * @param {TestItem[]} items
 */
var TestContainer = util.inherit(
	'TestContainer',
	GroupableCollection,
	function( items ) {
		this._items = items || [];
	},
{
	toArray: function() {
		return $.merge( [], this._items );
	},
	hasItem: function( item ) {
		return $.inArray( item, this._items ) !== -1;
	},
	addItem: function( item ) {
		this._items.push( item );
	},
	removeItem: function( item ) {
		for( var i = 0; i < this._items.length; i++ ) {
			if( this._items[i].equals( item ) ) {
				this._items.splice( i, 1 );
				break;
			}
		}
	},
	isEmpty: function() {
		return !!this._items.length;
	},
	equals: function( testContainer ) {
		if( testContainer === this ) {
			return true;
		} else if(
			!( testContainer instanceof this.constructor )
			|| testContainer.toArray().length !== this._items.length
		) {
			return false;
		}

		var otherItems = testContainer.toArray();

		for( var i = 0; i < this._items.length; i++ ) {
			if( !this._items[i].equals( otherItems[i] ) ) {
				return false;
			}
		}

		return true;
	},
	getItemKey: function( item ) {
		return item.getKey();
	},
	getKeys: function() {
		var keys = [];
		for( var i = 0; i < this._items.length; i++ ) {
			var key = this._items[i].getKey();
			if( $.inArray( key, keys ) === -1 ) {
				keys.push( key );
			}
		}
		return keys;
	}
} );

/**
 * @param {*} key
 * @param {number} n
 * @return {TestItem[]}
 */
function getTestItems( key, n ) {
	var items = [];

	for( var i = 0; i < n; i++ ) {
		items.push( new TestItem( key, i ) );
	}

	return items;
}

/**
 * @param {*} key
 * @param {number} n
 * @return {TestContainer}
 */
function getTestContainer( key, n ) {
	return new TestContainer( getTestItems( key, n ) );
}

/**
 * @param {*} key
 * @param {TestContainer} [container]
 * @return {Group}
 */
function createGroup( key, container ) {
	return new Group( key, TestContainer, 'getKeys', container );
}

QUnit.test( 'Constructor', function( assert ) {
	assert.expect( 6 );
	assert.ok(
		createGroup( 'key', new TestContainer() ) instanceof Group,
		'Instantiated empty Group.'
	);

	var group = createGroup( 'key', getTestContainer( 'key', 2 ) );

	assert.ok(
		group instanceof Group,
		'Instantiated filled Group.'
	);

	assert.equal(
		group.getKey(),
		'key',
		'Verified key being set.'
	);

	assert.ok(
		group.getItemContainer().equals( getTestContainer( 'key', 2 ) ),
		'Verified sub-widget being set.'
	);

	assert.throws(
		function() {
			createGroup( 'key', new TestContainer( ['string'] ) );
		},
		'Throwing an error when trying to instantiate a Group with a container featuring improper '
			+ 'items.'
	);

	assert.throws(
		function() {
			createGroup( 'key', getTestContainer( 'otherKey', 1 ) );
		},
		'Throwing error when trying to instantiate a Group with mismatching key.'
	);
} );

QUnit.test( 'setItemContainer() & getItemContainer()', function( assert ) {
	assert.expect( 4 );
	var container = getTestContainer( 'key', 1 ),
		group = createGroup( 'key', container ),
		newContainer = getTestContainer( 'key', 3 );

	assert.strictEqual(
		container,
		group.getItemContainer(),
		'getItemContainer() does not clone.'
	);

	assert.ok(
		group.getItemContainer().equals( container ),
		'Verified returned container matching returned container.'
	);

	group.setItemContainer( newContainer );

	assert.strictEqual(
		newContainer,
		group.getItemContainer(),
		'Set new container.'
	);

	assert.throws(
		function() {
			group.setItemContainer( getTestContainer( 'otherKey', 1 ) );
		},
		'Throwing error when trying to set a container with mismatching key.'
	);
} );

QUnit.test( 'addItem() & hasItem()', function( assert ) {
	assert.expect( 3 );
	var container = getTestContainer( 'key', 1 ),
		group = createGroup( 'key', container ),
		newItem = getTestItems( 'key', 2 )[1];

	assert.ok(
		!group.hasItem( newItem ),
		'Verified Group not containing item not yet added.'
	);

	group.addItem( newItem );

	assert.ok(
		group.hasItem( newItem ),
		'Added new item.'
	);

	assert.throws(
		function() {
			group.addItem( getTestItems( 'anotherKey', 2 )[1] );
		},
		'Throwing error when trying to add an item with mismatching key.'
	);
} );

QUnit.test( 'equals()', function( assert ) {
	assert.expect( 2 );
	var group = createGroup( 'key', getTestContainer( 'key', 1 ) );

	assert.ok(
		group.equals( createGroup( 'key', getTestContainer( 'key', 1 ) ) ),
		'Verified equals() retuning TRUE.'
	);

	group.addItem( getTestItems( 'key', 2 )[1] );

	assert.ok(
		!group.equals( createGroup( 'key', getTestContainer( 'key', 1 ) ) ),
		'FALSE compared to initial group after adding an item.'
	);
} );

}( QUnit, jQuery, util ) );

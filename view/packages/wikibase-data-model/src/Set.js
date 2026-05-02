( function( $ ) {
'use strict';

var PARENT = require( './GroupableCollection.js' );

/**
 * Stores items without imposing any order.
 * @class Set
 * @extends GroupableCollection
 * @since 1.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {Function} ItemConstructor
 * @param {string} itemKeyFunctionName
 * @param {*[]} [items=[]]
 *
 * @throws {Error} if item constructor is not a Function.
 * @throws {Error} if item constructor prototype does not feature an equals() function.
 * @throws {Error} if item constructor prototype does not feature the specified function to retrieve
 *         the item key.
 * @throws {Error} when the items array does contain items that feature the same key.
 */
module.exports = util.inherit(
	'WbDataModelSet',
	PARENT,
	function( ItemConstructor, itemKeyFunctionName, items ) {
		if( typeof ItemConstructor !== 'function' ) {
			throw new Error( 'Item constructor needs to be a Function' );
		} else if( typeof ItemConstructor.prototype.equals !== 'function' ) {
			throw new Error( 'List item prototype needs equals() method' );
		} else if( typeof ItemConstructor.prototype[itemKeyFunctionName] !== 'function' ) {
			throw new Error( 'Missing ' + itemKeyFunctionName + '() in list item prototype to '
				+ ' receive the item key from' );
		}

		items = items || [];

		this._ItemConstructor = ItemConstructor;
		this._itemKeyFunctionName = itemKeyFunctionName;
		this._items = {};

		for( var i = 0; i < items.length; i++ ) {
			this._assertIsItem( items[i] );

			var key = this.getItemKey( items[i] );

			if( this._items[key] ) {
				throw new Error( 'There may only be one item per item key' );
			}

			this.length++;
			this._items[key] = items[i];
		}
	},
{
	/**
	 * @property {Function}
	 * @private
	 */
	_ItemConstructor: null,

	/**
	 * @property {string}
	 * @private
	 */
	_itemKeyFunctionName: null,

	/**
	 * @property {Object}
	 * @protected
	 */
	_items: null,

	/**
	 * @property {number}
	 * @readonly
	 */
	length: 0,

	/**
	 * @see jQuery.fn.each
	 *
	 * @param {Function} fn
	 */
	each: function( fn ) {
		$.each.call( null, this._items, fn );
	},

	/**
	 * @inheritdoc
	 */
	toArray: function() {
		var items = [];

		for( var key in this._items ) {
			items.push( this._items[key] );
		}

		return items;
	},

	/**
	 * @inheritdoc
	 */
	hasItem: function( item ) {
		this._assertIsItem( item );
		var key = this.getItemKey( item );
		return this._items[key] && this._items[key].equals( item );
	},

	/**
	 * @inheritdoc
	 *
	 * @throws {Error} when trying to add an item that is registered already.
	 */
	addItem: function( item ) {
		this._assertIsItem( item );

		if( this.hasItem( item ) ) {
			throw new Error( 'Item with key ' + this.getItemKey( item ) + ' exists already' );
		}

		this.setItem( item );
	},

	/**
	 * @inheritdoc
	 *
	 * throws {Error} when trying to remove an item that is not registered.
	 */
	removeItem: function( item ) {
		this._assertIsItem( item );

		var key = this.getItemKey( item );

		if( item.equals( this._items[key] ) ) {
			this.removeItemByKey( key );
		} else {
			throw new Error( 'Trying to remove non-existent item' );
		}
	},

	/**
	 * @inheritdoc
	 */
	isEmpty: function() {
		return this.length === 0;
	},

	/**
	 * @inheritdoc
	 */
	equals: function( set ) {
		if( set === this ) {
			return true;
		} else if ( !( set instanceof this.constructor ) || this.length !== set.length ) {
			return false;
		}

		for( var key in this._items ) {
			if( !set.hasItem( this._items[key] ) ) {
				return false;
			}
		}

		return true;
	},

	/**
	 * @inheritdoc
	 */
	getItemKey: function( item ) {
		return item[this._itemKeyFunctionName]();
	},

	/**
	 * @return {string[]}
	 */
	getKeys: function() {
		var keys = [];

		for( var key in this._items ) {
			keys.push( key );
		}

		return keys;
	},

	/**
	 * @param {string} key
	 * @return {*|null}
	 */
	getItemByKey: function( key ) {
		return this._items[key] || null;
	},

	/**
	 * @param {string} key
	 */
	removeItemByKey: function( key ) {
		if( this._items[key] ) {
			this.length--;
		}
		delete this._items[key];
	},

	/**
	 * @param {string} key
	 * @return {boolean}
	 */
	hasItemForKey: function( key ) {
		return !!this._items[key];
	},

	/**
	 * @param {*} item
	 */
	setItem: function( item ) {
		this._assertIsItem( item );

		var key = this.getItemKey( item );

		if( !this._items[key] ) {
			this.length++;
		}

		this._items[key] = item;
	},

	/**
	 * @private
	 *
	 * @param {*} item
	 *
	 * @throws {Error} if the item is not an instance of the constructor registered with the Set
	 *         object.
	 */
	_assertIsItem: function( item ) {
		if( !( item instanceof this._ItemConstructor ) ) {
			throw new Error( 'Item is not an instance of the constructor set on the list' );
		}
	}

} );

}( jQuery ) );

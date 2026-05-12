( function( $ ) {
'use strict';

var PARENT = require( './GroupableCollection.js' );

/**
 * Stores items in order.
 * @class List
 * @extends GroupableCollection
 * @since 1.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {Function} ItemConstructor
 * @param {*[]} [items=[]]
 *
 * @throws {Error} if item constructor is not a Function.
 * @throws {Error} if item constructor prototype does not feature an equals() function.
 */
module.exports = util.inherit(
	'WbDataModelList',
	PARENT,
	function( ItemConstructor, items ) {
		if( typeof ItemConstructor !== 'function' ) {
			throw new Error( 'Item constructor needs to be a Function' );
		} else if( typeof ItemConstructor.prototype.equals !== 'function' ) {
			throw new Error( 'List item prototype needs equals() method' );
		}

		items = items || [];

		this._ItemConstructor = ItemConstructor;

		for( var i = 0; i < items.length; i++ ) {
			this._assertIsItem( items[i] );
		}

		this._items = items.slice();
		this.length = items.length;
	},
{
	/**
	 * @property {Function}
	 * @private
	 */
	_ItemConstructor: null,

	/**
	 * @property {*[]}
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
		return this._items;
	},

	/**
	 * @inheritdoc
	 */
	hasItem: function( item ) {
		this._assertIsItem( item );

		for( var i = 0; i < this._items.length; i++ ) {
			if( this._items[i].equals( item ) ) {
				return true;
			}
		}
		return false;
	},

	/**
	 * @inheritdoc
	 */
	addItem: function( item ) {
		this._assertIsItem( item );

		this._items.push( item );
		this.length++;
	},

	/**
	 * @inheritdoc
	 *
	 * @throws {Error} when trying to remove an item which is not registered.
	 */
	removeItem: function( item ) {
		this._assertIsItem( item );

		for( var i = 0; i < this._items.length; i++ ) {
			if( this._items[i].equals( item ) ) {
				this._items.splice( i, 1 );
				this.length--;
				return;
			}
		}
		throw new Error( 'Trying to remove a non-existing item' );
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
	equals: function( list ) {
		if( list === this ) {
			return true;
		} else if( !( list instanceof this.constructor ) || this.length !== list.length ) {
			return false;
		}

		for( var i = 0; i < this._items.length; i++ ) {
			if( list.indexOf( this._items[i] ) !== i ) {
				return false;
			}
		}

		return true;
	},

	/**
	 * @inheritdoc
	 *
	 * @throws {Error} when trying to retrieve a key for an item since a list does not feature
	 *         unique keys.
	 */
	getItemKey: function( item ) {
		throw new Error( 'List does not feature any unique keys' );
	},

	/**
	 * @param {*} item
	 * @return {number}
	 */
	indexOf: function( item ) {
		this._assertIsItem( item );

		for( var i = 0; i < this._items.length; i++ ) {
			if( this._items[i].equals( item ) ) {
				return i;
			}
		}
		return -1;
	},

	/**
	 * @private
	 *
	 * @param {*} item
	 *
	 * @throws {Error} if the item is not an instance of the constructor registered with the List
	 *         object.
	 */
	_assertIsItem: function( item ) {
		if( !( item instanceof this._ItemConstructor ) ) {
			throw new Error( 'Item is not an instance of the constructor set on the list' );
		}
	}

} );

}( jQuery ) );

/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
'use strict';

/**
 * Unordered set.
 * @constructor
 * @since 0.4
 *
 * @param {Function} ItemConstructor
 * @param {string} itemKeyFunctionName
 * @param {*[]} [items]
 */
var SELF = wb.datamodel.UnorderedList
	= function WbDataModelUnorderedList( ItemConstructor, itemKeyFunctionName, items ) {

	if( !$.isFunction( ItemConstructor ) ) {
		throw new Error( 'Item constructor needs to be a Function' );
	} else if( !$.isFunction( ItemConstructor.prototype.equals ) ) {
		throw new Error( 'List item prototype needs equals() method' );
	} else if( !$.isFunction( ItemConstructor.prototype[itemKeyFunctionName] ) ) {
		throw new Error( 'Missing ' + itemKeyFunctionName + '() in list item prototype to receive '
			+ 'the item key from' );
	}

	items = items || [];

	this._ItemConstructor = ItemConstructor;
	this._itemKeyFunctionName = itemKeyFunctionName;
	this._items = {};
	this.length = 0;

	for( var i = 0; i < items.length; i++ ) {
		this._assertIsItem( items[i] );

		if( this._items[this._getItemKey( items[i] )] ) {
			throw new Error( 'There may only be one item per item key' );
		}

		this.setItem( items[i] );
	}
};

$.extend( SELF.prototype, {
	/**
	 * @type {Function}
	 */
	_ItemConstructor: null,

	/**
	 * @type {string}
	 */
	_itemKeyFunctionName: null,

	/**
	 * @type {Object}
	 */
	_items: null,

	/**
	 * @type {number}
	 */
	length: 0,

	/**
	 * @see jQuery.fn.each
	 */
	each: function( fn ) {
		$.each.call( null, this._items, fn );
	},

	/**
	 * @param {*} item
	 * @return {string}
	 */
	_getItemKey: function( item ) {
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
	getByKey: function( key ) {
		return this._items[key] || null;
	},

	/**
	 * @param {string} key
	 */
	removeByKey: function( key ) {
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

		var key = this._getItemKey( item );

		if( !this._items[key] ) {
			this.length++;
		}

		this._items[key] = item;
	},

	/**
	 * @param {*} item
	 */
	removeItem: function( item ) {
		this._assertIsItem( item );

		var key = this._getItemKey( item );

		if( item.equals( this._items[key] ) ) {
			this.removeByKey( key );
		} else {
			throw new Error( 'Trying to remove non-existent item' );
		}
	},

	/**
	 * @return {boolean}
	 */
	isEmpty: function() {
		return this.length === 0;
	},

	/**
	 * @param {*} unorderedList
	 * @return {boolean}
	 */
	equals: function( unorderedList ) {
		if( unorderedList === this ) {
			return true;
		} else if ( !( unorderedList instanceof SELF ) || this.length !== unorderedList.length ) {
			return false;
		}

		for( var key in this._items ) {
			if( !unorderedList.hasItem( this._items[key] ) ) {
				return false;
			}
		}

		return true;
	},

	/**
	 * @param {*} item
	 * @return {boolean}
	 */
	hasItem: function( item ) {
		this._assertIsItem( item );
		var key = this._getItemKey( item );
		return this._items[key] && this._items[key].equals( item );
	},

	/**
	 * @param {*} item
	 */
	_assertIsItem: function( item ) {
		if( !( item instanceof this._ItemConstructor ) ) {
			throw new Error( 'Item is not an instance of the constructor set on the list' );
		}
	}

} );

}( wikibase, jQuery ) );

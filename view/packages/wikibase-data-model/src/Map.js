/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
'use strict';

/**
 * @constructor
 * @since 1.0
 *
 * @param {Function} ItemConstructor
 * @param {Object} [map]
 */
var SELF = wb.datamodel.Map = function( ItemConstructor, map ) {
	map = map || {};

	if( !$.isFunction( ItemConstructor ) ) {
		throw new Error( 'Item constructor needs to be a Function' );
	} else if( !$.isFunction( ItemConstructor.prototype.equals ) ) {
		throw new Error( 'Map item prototype needs equals() method' );
	}

	this._ItemConstructor = ItemConstructor;
	this._items = {};

	for( var key in map ) {
		this.setItem( key, map[key] );
	}
};

$.extend( SELF.prototype, {
	/**
	 * @type {Function}
	 */
	_ItemConstructor: null,

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
	 * @param {string} key
	 * @param {Object} item
	 * @return {boolean}
	 */
	hasItem: function( key, item ) {
		this._assertIsItem( item );
		return this._items[key] && this._items[key].equals( item );
	},

	/**
	 * @param {string} key
	 * @param {Object} item
	 */
	addItem: function( key, item ) {
		this._assertIsItem( item );

		if( this.hasItemForKey( key ) ) {
			throw new Error( 'Item for key ' + key + ' exists already' );
		}

		this.setItem( key, item );
	},

	/**
	 * @param {string} key
	 * @param {Object} item
	 */
	removeItem: function( key, item ) {
		if( !this.hasItem( key, item ) ) {
			throw new Error( 'Item for key ' + key + ' to be removed does not exist' );
		}
		this.removeItemByKey( key );
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
	 * @param {string} key
	 * @param {Object} item
	 */
	setItem: function( key, item ) {
		this._assertIsItem( item );

		if( !this.hasItemForKey( key ) ) {
			this.length++;
		}

		this._items[key] = item;
	},

	/**
	 * @return {boolean}
	 */
	isEmpty: function() {
		return this.length === 0;
	},

	/**
	 * @param {wikibase.datamodel.Map} map
	 * @return {boolean}
	 */
	equals: function( map ) {
		if( !( map instanceof SELF ) || map.length !== this.length ) {
			return false;
		}

		for( var key in this._items ) {
			if( !map.hasItem( key, this._items[key] ) ) {
				return false;
			}
		}

		return true;
	},

	/**
	 * @param {*} item
	 * @return {boolean}
	 */
	_assertIsItem: function( item ) {
		if( !( item instanceof this._ItemConstructor ) ) {
			throw new Error( 'Item is not an instance of the constructor set on the map' );
		}
	}
} );

}( wikibase, jQuery ) );

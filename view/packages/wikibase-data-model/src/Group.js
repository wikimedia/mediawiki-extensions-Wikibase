/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
'use strict';

/**
 * A Group contains a list of which all items feature the key specified with the Group.
 * @constructor
 * @since 0.4
 *
 * @param {*} key
 * @param {Function} ItemListConstructor
 * @param {string} itemListKeysFunctionName
 * @param {*} itemList
 */
var SELF = wb.datamodel.Group = function WbDataModelGroup(
	key,
	ItemListConstructor,
	itemListKeysFunctionName,
	itemList
) {
	if( key === undefined ) {
		throw new Error( 'Key may not be undefined' );
	} else if( !$.isFunction( ItemListConstructor ) ) {
		throw new Error( 'Item list constructor needs to be a Function' );
	} else if(
		// TODO: Implement abstract base class for UnorderedList and OrderedList
		!$.isFunction( ItemListConstructor.prototype.toArray )
		|| !$.isFunction( ItemListConstructor.prototype.hasItem )
		|| !$.isFunction( ItemListConstructor.prototype.addItem )
		|| !$.isFunction( ItemListConstructor.prototype.removeItem )
		|| !$.isFunction( ItemListConstructor.prototype.isEmpty )
		|| !$.isFunction( ItemListConstructor.prototype.equals )
		|| !$.isFunction( ItemListConstructor.prototype.getItemKey )
	) {
		throw new Error( 'Item prototype needs equals() method' );
	} else if( !$.isFunction( ItemListConstructor.prototype[itemListKeysFunctionName] ) ) {
		throw new Error( 'Missing ' + ItemListConstructor + '() in list item prototype to receive '
			+ 'the item key from' );
	}

	this._key = key;
	this._ItemListConstructor = ItemListConstructor;
	this._itemListKeysFunctionName = itemListKeysFunctionName;
	this.setItemList( itemList || new ItemListConstructor() );
};

$.extend( SELF.prototype, {
	/**
	 * @type {*}
	 */
	_key: null,

		/**
	 * @type {Function}
	 */
	_ItemListConstructor: null,

	/**
	 * @type {string}
	 */
	_itemListKeysFunctionName: null,

	/**
	 * @type {*}
	 */
	_items: null,

	/**
	 * @return {*}
	 */
	getKey: function() {
		return this._key;
	},

	/**
	 * @param {*} itemList
	 * @return {string}
	 */
	getItemListKeys: function( itemList ) {
		return itemList[this._itemListKeysFunctionName]();
	},

	/**
	 * @return {*}
	 */
	getItemList: function() {
		// Do not allow altering the encapsulated ClaimList.
		return new this._ItemListConstructor( this._itemList.toArray() );
	},

	/**
	 * @param {*} itemList
	 */
	setItemList: function( itemList ) {
		var keys = this.getItemListKeys( itemList );

		for( var i = 0; i < keys.length; i++ ) {
			if( keys[i] !== this._key ) {
				throw new Error( 'Mismatching key: Expected ' + this._key + ', received '
					+ keys[i] );
			}
		}

		this._itemList = itemList;
	},

	/**
	 * @param {*} item
	 * @return {boolean}
	 */
	hasItem: function( item ) {
		return this._itemList.hasItem( item );
	},

	/**
	 * @param {*} item
	 */
	addItem: function( item ) {
		if( this._itemList.getItemKey( item ) !== this._key ) {
			throw new Error(
				'Mismatching key: Expected ' + this._key + ', received '
					+ this._itemList.getItemKey( item )
			);
		}
		this._itemList.addItem( item );
	},

	/**
	 * @param {*} item
	 */
	removeItem: function( item ) {
		this._itemList.removeItem( item );
	},

	/**
	 * @return {boolean}
	 */
	isEmpty: function() {
		return this._itemList.isEmpty();
	},

	/**
	 * @param {*} group
	 * @return {boolean}
	 */
	equals: function( group ) {
		return group === this
			|| group instanceof SELF
			&& this._key === group.getKey()
			&& this._itemList.equals( group.getItemList() );
	}

} );

}( wikibase, jQuery ) );

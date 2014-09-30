/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
'use strict';

/**
 * A Group referencing a container of which all items feature the key specified with the Group.
 * @constructor
 * @since 1.0
 *
 * @param {*} key
 * @param {Function} ItemContainerConstructor
 * @param {string} itemContainerKeysFunctionName
 * @param {wikibase.datamodel.Groupable} itemContainer
 */
var SELF = wb.datamodel.Group = function WbDataModelGroup(
	key,
	ItemContainerConstructor,
	itemContainerKeysFunctionName,
	itemContainer
) {
	if( key === undefined ) {
		throw new Error( 'Key may not be undefined' );
	} else if( !$.isFunction( ItemContainerConstructor ) ) {
		throw new Error( 'Item container constructor needs to be a Function' );
	} else if( !( new ItemContainerConstructor() ) instanceof wb.datamodel.Groupable ) {
		throw new Error( 'Item container constructor needs to implement Groupable' );
	} else if(
		!$.isFunction( ItemContainerConstructor.prototype[itemContainerKeysFunctionName] )
	) {
		throw new Error( 'Missing ' + ItemContainerConstructor + '() in container item prototype '
			+ 'to receive the item key from' );
	}

	this._key = key;
	this._ItemContainerConstructor = ItemContainerConstructor;
	this._itemContainerKeysFunctionName = itemContainerKeysFunctionName;
	this.setItemContainer( itemContainer || new ItemContainerConstructor() );
};

$.extend( SELF.prototype, {
	/**
	 * @type {*}
	 */
	_key: null,

		/**
	 * @type {Function}
	 */
	_ItemContainerConstructor: null,

	/**
	 * @type {string}
	 */
	_itemContainerKeysFunctionName: null,

	/**
	 * @type {wikibase.datamodel.Groupable}
	 */
	_itemContainer: null,

	/**
	 * @return {*}
	 */
	getKey: function() {
		return this._key;
	},

	/**
	 * @return {*}
	 */
	getItemContainer: function() {
		// Do not allow altering the encapsulated container.
		return new this._ItemContainerConstructor( this._itemContainer.toArray() );
	},

	/**
	 * @param {*} itemContainer
	 */
	setItemContainer: function( itemContainer ) {
		var keys = this._getItemContainerKeys( itemContainer );

		for( var i = 0; i < keys.length; i++ ) {
			if( keys[i] !== this._key ) {
				throw new Error( 'Mismatching key: Expected ' + this._key + ', received '
					+ keys[i] );
			}
		}

		// Clone the container to prevent manipulation of the items using the original container.
		this._itemContainer = new this._ItemContainerConstructor( itemContainer.toArray() );
	},

	/**
	 * @param {*} itemContainer
	 * @return {string}
	 */
	_getItemContainerKeys: function( itemContainer ) {
		return itemContainer[this._itemContainerKeysFunctionName]();
	},

	/**
	 * @param {*} item
	 * @return {boolean}
	 */
	hasItem: function( item ) {
		return this._itemContainer.hasItem( item );
	},

	/**
	 * @param {*} item
	 */
	addItem: function( item ) {
		if( this._itemContainer.getItemKey( item ) !== this._key ) {
			throw new Error(
				'Mismatching key: Expected ' + this._key + ', received '
					+ this._itemContainer.getItemKey( item )
			);
		}
		this._itemContainer.addItem( item );
	},

	/**
	 * @param {*} item
	 */
	removeItem: function( item ) {
		this._itemContainer.removeItem( item );
	},

	/**
	 * @return {boolean}
	 */
	isEmpty: function() {
		return this._itemContainer.isEmpty();
	},

	/**
	 * @param {*} group
	 * @return {boolean}
	 */
	equals: function( group ) {
		return group === this
			|| group instanceof SELF
			&& this._key === group.getKey()
			&& this._itemContainer.equals( group.getItemContainer() );
	}

} );

}( wikibase, jQuery ) );

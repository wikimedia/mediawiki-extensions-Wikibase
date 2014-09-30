/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
'use strict';

/**
 * References a container of which all items feature the key specified with the Group.
 * @constructor
 * @since 1.0
 *
 * @param {*} key
 * @param {Function} GroupableCollectionConstructor
 * @param {string} groupableCollectionGetKeysFunctionName
 * @param {wikibase.datamodel.GroupableCollection} groupableCollection
 */
var SELF = wb.datamodel.Group = function WbDataModelGroup(
	key,
	GroupableCollectionConstructor,
	groupableCollectionGetKeysFunctionName,
	groupableCollection
) {
	if( key === undefined ) {
		throw new Error( 'Key may not be undefined' );
	} else if( !$.isFunction( GroupableCollectionConstructor ) ) {
		throw new Error( 'Item container constructor needs to be a Function' );
	} else if(
		!( new GroupableCollectionConstructor() ) instanceof wb.datamodel.GroupableCollection
	) {
		throw new Error( 'Item container constructor needs to implement GroupableCollection' );
	} else if(
		!$.isFunction(
			GroupableCollectionConstructor.prototype[groupableCollectionGetKeysFunctionName]
		)
	) {
		throw new Error( 'Missing ' + GroupableCollectionConstructor + '() in container item '
			+ 'prototype to receive the item key from' );
	}

	this._key = key;
	this._GroupableCollectionConstructor = GroupableCollectionConstructor;
	this._groupableCollectionGetKeysFunctionName = groupableCollectionGetKeysFunctionName;
	this.setItemContainer( groupableCollection || new GroupableCollectionConstructor() );
};

$.extend( SELF.prototype, {
	/**
	 * @type {*}
	 */
	_key: null,

	/**
	 * @type {Function}
	 */
	_GroupableCollectionConstructor: null,

	/**
	 * @type {string}
	 */
	_groupableCollectionGetKeysFunctionName: null,

	/**
	 * @type {wikibase.datamodel.GroupableCollection}
	 */
	_groupableCollection: null,

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
		return new this._GroupableCollectionConstructor( this._groupableCollection.toArray() );
	},

	/**
	 * @param {*} groupableCollection
	 */
	setItemContainer: function( groupableCollection ) {
		var keys = this._getItemContainerKeys( groupableCollection );

		for( var i = 0; i < keys.length; i++ ) {
			if( keys[i] !== this._key ) {
				throw new Error( 'Mismatching key: Expected ' + this._key + ', received '
					+ keys[i] );
			}
		}

		// Clone the container to prevent manipulation of the items using the original container.
		this._groupableCollection = new this._GroupableCollectionConstructor(
			groupableCollection.toArray()
		);
	},

	/**
	 * @param {*} groupableCollection
	 * @return {string}
	 */
	_getItemContainerKeys: function( groupableCollection ) {
		return groupableCollection[this._groupableCollectionGetKeysFunctionName]();
	},

	/**
	 * @param {*} item
	 * @return {boolean}
	 */
	hasItem: function( item ) {
		return this._groupableCollection.hasItem( item );
	},

	/**
	 * @param {*} item
	 */
	addItem: function( item ) {
		if( this._groupableCollection.getItemKey( item ) !== this._key ) {
			throw new Error(
				'Mismatching key: Expected ' + this._key + ', received '
					+ this._groupableCollection.getItemKey( item )
			);
		}
		this._groupableCollection.addItem( item );
	},

	/**
	 * @param {*} item
	 */
	removeItem: function( item ) {
		this._groupableCollection.removeItem( item );
	},

	/**
	 * @return {boolean}
	 */
	isEmpty: function() {
		return this._groupableCollection.isEmpty();
	},

	/**
	 * @param {*} group
	 * @return {boolean}
	 */
	equals: function( group ) {
		return group === this
			|| group instanceof SELF
				&& this._key === group.getKey()
				&& this._groupableCollection.equals( group.getItemContainer() );
	}

} );

}( wikibase, jQuery ) );
